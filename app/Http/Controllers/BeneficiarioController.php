<?php

namespace App\Http\Controllers;

use App\Enums\StatusContrato;
use App\Models\Colaborador;
use App\Models\Fornecedor;
use App\Support\Documento;
use App\Support\Permissoes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Busca de beneficiários para os formulários de lançamento.
 *
 * Responde JSON, não Inertia: o formulário de pagamento precisa consultar sob
 * demanda enquanto o usuário digita, sem trocar de página.
 *
 * Existe porque o formulário depende de três encadeamentos — o tipo filtra a
 * busca, o beneficiário filtra as contas, e Pix/TED exigem conta — e carregar
 * todo o cadastro de uma vez não escalaria.
 */
class BeneficiarioController extends Controller
{
    private const LIMITE_BUSCA = 20;

    /**
     * Beneficiários ativos que casam com o termo digitado.
     */
    public function buscar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'tipo' => ['required', 'in:colaborador,fornecedor'],
            'termo' => ['nullable', 'string', 'max:255'],
        ]);

        $this->garantirPermissaoDeLeitura($dados['tipo']);

        $termo = $dados['termo'] ?? null;

        $resultados = $dados['tipo'] === 'colaborador'
            ? $this->buscarColaboradores($termo)
            : $this->buscarFornecedores($termo);

        return response()->json(['dados' => $resultados]);
    }

    /**
     * Contas de destino e contratos do beneficiário escolhido.
     *
     * Só contas ativas: o PagamentoService recusa lançamento em conta inativa, e
     * oferecer uma na tela só produziria um erro depois de o formulário inteiro
     * já estar preenchido.
     */
    public function dados(Request $request, string $tipo, int $id): JsonResponse
    {
        abort_unless(in_array($tipo, ['colaborador', 'fornecedor'], true), 404);

        $this->garantirPermissaoDeLeitura($tipo);

        $beneficiario = $this->resolver($tipo, $id);

        $contas = $beneficiario->contasAtivas()
            ->orderByDesc('principal')
            ->get()
            ->map(fn ($conta) => [
                'id' => $conta->id,
                'resumo' => $conta->resumo,
                'principal' => $conta->principal,
                'chave_pix' => $conta->chave_pix,
                'tipo_chave_pix' => $conta->tipo_chave_pix?->value,
            ]);

        // Contrato só existe para fornecedor (regra 3.4).
        $contratos = $beneficiario instanceof Fornecedor
            ? $beneficiario->contratos()
                ->where('status', StatusContrato::Ativo)
                ->orderBy('descricao')
                ->get(['id', 'descricao', 'valor', 'categoria_id'])
            : collect();

        return response()->json([
            'contas' => $contas,
            'contratos' => $contratos,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buscarColaboradores(?string $termo): array
    {
        return Colaborador::query()
            ->ativos()
            ->busca($termo)
            ->orderBy('nome')
            ->limit(self::LIMITE_BUSCA)
            ->get(['id', 'nome', 'cpf', 'cargo', 'departamento'])
            ->map(fn (Colaborador $c) => [
                'id' => $c->id,
                'tipo' => 'colaborador',
                'nome' => $c->nome,
                'documento' => Documento::formatar($c->cpf),
                'detalhe' => "{$c->cargo} · {$c->departamento}",
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buscarFornecedores(?string $termo): array
    {
        return Fornecedor::query()
            ->ativos()
            ->busca($termo)
            ->orderBy('razao_social')
            ->limit(self::LIMITE_BUSCA)
            ->get(['id', 'razao_social', 'nome_fantasia', 'documento', 'tipo_fornecedor'])
            ->map(fn (Fornecedor $f) => [
                'id' => $f->id,
                'tipo' => 'fornecedor',
                'nome' => $f->nome_exibicao,
                'documento' => Documento::formatar($f->documento),
                'detalhe' => $f->tipo_fornecedor->rotulo(),
            ])
            ->all();
    }

    private function resolver(string $tipo, int $id): Model
    {
        return match ($tipo) {
            'colaborador' => Colaborador::findOrFail($id),
            'fornecedor' => Fornecedor::findOrFail($id),
        };
    }

    private function garantirPermissaoDeLeitura(string $tipo): void
    {
        $permissao = $tipo === 'colaborador'
            ? Permissoes::COLABORADORES_VER
            : Permissoes::FORNECEDORES_VER;

        abort_unless(request()->user()->can($permissao), 403);
    }
}
