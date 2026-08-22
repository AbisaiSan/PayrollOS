<?php

namespace App\Services;

use App\Models\Colaborador;
use App\Models\Fornecedor;
use App\Models\Pagamento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Consolidacao de pagamentos por periodo (Fase 6 do plano).
 *
 * Existe como service, e nao como metodo privado do controller, porque a tela e
 * o arquivo exportado precisam sair do mesmo lugar. O modal de exportacao promete
 * "mantem os filtros aplicados na tela"; se cada lado montasse a propria consulta,
 * a promessa duraria ate a primeira mudanca de regra em um so deles.
 */
class RelatorioService
{
    /**
     * Normaliza os filtros da requisicao.
     *
     * Sem periodo, o mes corrente. A tela mostra qual intervalo esta em uso
     * justamente porque essa escolha e do backend, nao do usuario.
     *
     * @return array{inicio: string, fim: string, categoria_id: ?int, status: ?string}
     */
    public function filtros(Request $request): array
    {
        return [
            'inicio' => $request->string('inicio')->toString() ?: now()->startOfMonth()->toDateString(),
            'fim' => $request->string('fim')->toString() ?: now()->endOfMonth()->toDateString(),
            'categoria_id' => $request->integer('categoria_id') ?: null,
            'status' => $request->string('status')->toString() ?: null,
        ];
    }

    /**
     * @param  array{inicio: string, fim: string, categoria_id: ?int, status: ?string}  $filtros
     */
    public function consulta(array $filtros): Builder
    {
        return Pagamento::query()
            ->noPeriodo($filtros['inicio'], $filtros['fim'])
            ->when($filtros['categoria_id'], fn ($q, $id) => $q->where('categoria_id', $id))
            ->when($filtros['status'], fn ($q, $status) => $q->where('status', $status));
    }

    /**
     * @param  array{inicio: string, fim: string, categoria_id: ?int, status: ?string}  $filtros
     * @return array{total: float, quantidade: int}
     */
    public function resumo(array $filtros): array
    {
        $consulta = $this->consulta($filtros);

        return [
            'total' => (float) (clone $consulta)->sum('valor'),
            'quantidade' => (clone $consulta)->count(),
        ];
    }

    /**
     * @param  array{inicio: string, fim: string, categoria_id: ?int, status: ?string}  $filtros
     * @return Collection<int, array<string, mixed>>
     */
    public function porStatus(array $filtros): Collection
    {
        return $this->consulta($filtros)
            ->groupBy('status')
            ->select('status', DB::raw('SUM(valor) as total'), DB::raw('COUNT(*) as quantidade'))
            ->get()
            ->map(fn ($linha) => [
                'status' => $linha->status->value,
                'rotulo' => $linha->status->rotulo(),
                'total' => (float) $linha->total,
                'quantidade' => (int) $linha->quantidade,
            ]);
    }

    /**
     * @param  array{inicio: string, fim: string, categoria_id: ?int, status: ?string}  $filtros
     * @return Collection<int, array<string, mixed>>
     */
    public function porCategoria(array $filtros): Collection
    {
        return $this->consulta($filtros)
            ->join('categorias_pagamento', 'categorias_pagamento.id', '=', 'pagamentos.categoria_id')
            ->groupBy('categorias_pagamento.nome')
            ->select('categorias_pagamento.nome', DB::raw('SUM(pagamentos.valor) as total'))
            ->orderByDesc('total')
            ->get()
            ->map(fn ($linha) => ['nome' => $linha->nome, 'total' => (float) $linha->total]);
    }

    /**
     * Os lancamentos que sustentam os agregados.
     *
     * Sao eles que dao ao arquivo exportado utilidade que a tela nao tem: a tela
     * responde "quanto"; o arquivo precisa responder "quais", senao nao serve para
     * conferir nada.
     *
     * @param  array{inicio: string, fim: string, categoria_id: ?int, status: ?string}  $filtros
     * @return Collection<int, array<string, mixed>>
     */
    public function lancamentos(array $filtros): Collection
    {
        return $this->consulta($filtros)
            ->with(['categoria:id,nome', 'payable'])
            ->orderBy('data_vencimento')
            ->get()
            ->map(fn (Pagamento $pagamento) => [
                'id' => $pagamento->id,
                'descricao' => $pagamento->descricao,
                'beneficiario' => $this->nomeBeneficiario($pagamento),
                'beneficiario_tipo' => $pagamento->payable_type === 'colaborador'
                    ? 'Colaborador'
                    : 'Fornecedor',
                'categoria' => $pagamento->categoria?->nome ?? '—',
                'competencia' => $pagamento->competencia,
                'data_vencimento' => $pagamento->data_vencimento->format('d/m/Y'),
                'data_pagamento' => $pagamento->data_pagamento?->format('d/m/Y'),
                'forma_pagamento' => $pagamento->forma_pagamento->rotulo(),
                'status' => $pagamento->status->rotulo(),
                'valor' => (float) $pagamento->valor,
            ]);
    }

    private function nomeBeneficiario(Pagamento $pagamento): string
    {
        $beneficiario = $pagamento->payable;

        return match (true) {
            $beneficiario instanceof Colaborador => $beneficiario->nome,
            $beneficiario instanceof Fornecedor => $beneficiario->nome_exibicao,
            default => '—',
        };
    }
}
