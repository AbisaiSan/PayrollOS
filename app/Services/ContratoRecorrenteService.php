<?php

namespace App\Services;

use App\Enums\FormaPagamento;
use App\Enums\StatusPagamento;
use App\Enums\TipoCategoria;
use App\Models\CategoriaPagamento;
use App\Models\Contrato;
use App\Models\Pagamento;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Gera os lancamentos dos contratos recorrentes alguns dias antes do vencimento
 * (regra 3.6). Os pagamentos nascem com status Pendente e so saem dele por
 * confirmacao manual.
 */
class ContratoRecorrenteService
{
    public function __construct(private readonly PagamentoService $pagamentos) {}

    /**
     * Percorre os contratos recorrentes ativos e gera o que estiver dentro da janela.
     *
     * @return array{gerados: int, contratos: int, ignorados: array<int, string>}
     */
    public function gerarLancamentosPendentes(?int $diasAntecedencia = null, ?Carbon $referencia = null): array
    {
        $diasAntecedencia ??= (int) config('payrollos.antecedencia_geracao_dias', 5);
        $referencia = ($referencia ?? now())->startOfDay();
        $limite = $referencia->copy()->addDays($diasAntecedencia);

        $gerados = 0;
        $ignorados = [];

        $contratos = Contrato::query()
            ->recorrentesAtivos()
            ->with('fornecedor')
            ->get();

        foreach ($contratos as $contrato) {
            if (! $contrato->geraLancamentosAutomaticos()) {
                continue;
            }

            $motivo = $this->motivoParaIgnorar($contrato);

            if ($motivo !== null) {
                $ignorados[$contrato->id] = $motivo;
                Log::warning("PayrollOS: contrato {$contrato->id} ignorado na geração automática.", ['motivo' => $motivo]);

                continue;
            }

            $gerados += $this->gerarParaContrato($contrato, $limite);
        }

        return [
            'gerados' => $gerados,
            'contratos' => $contratos->count(),
            'ignorados' => $ignorados,
        ];
    }

    /**
     * Gera todos os vencimentos do contrato ate a data limite.
     *
     * O laco existe porque a rotina pode ter ficado dias sem rodar: nesse caso um
     * contrato quinzenal teria mais de um vencimento pendente.
     */
    public function gerarParaContrato(Contrato $contrato, Carbon $limite): int
    {
        $vencimento = $contrato->proximo_vencimento
            ? Carbon::parse($contrato->proximo_vencimento)
            : $this->primeiroVencimento($contrato);

        $gerados = 0;

        while ($vencimento->lte($limite)) {
            if (! $contrato->vigenteEm($vencimento)) {
                break;
            }

            if ($this->gerarLancamento($contrato, $vencimento)) {
                $gerados++;
            }

            $vencimento = $contrato->periodicidade->proximoVencimento($vencimento);
        }

        $contrato->update(['proximo_vencimento' => $vencimento]);

        return $gerados;
    }

    /**
     * Cria um lancamento para a competencia, se ainda nao existir.
     *
     * A checagem de duplicidade e por (contrato, data de vencimento), o que torna a
     * rotina segura para rodar mais de uma vez no mesmo dia.
     */
    private function gerarLancamento(Contrato $contrato, Carbon $vencimento): bool
    {
        $jaExiste = Pagamento::query()
            ->where('contrato_id', $contrato->id)
            ->whereDate('data_vencimento', $vencimento->toDateString())
            ->exists();

        if ($jaExiste) {
            return false;
        }

        $conta = $contrato->conta_bancaria_id
            ? $contrato->contaBancaria
            : $contrato->fornecedor->contaPrincipal;

        DB::transaction(function () use ($contrato, $vencimento, $conta) {
            $pagamento = Pagamento::create([
                'payable_type' => $contrato->fornecedor->getMorphClass(),
                'payable_id' => $contrato->fornecedor_id,
                'categoria_id' => $contrato->categoria_id ?? $this->categoriaPadraoServico()?->id,
                'contrato_id' => $contrato->id,
                'conta_bancaria_id' => $conta?->id,
                'competencia' => $vencimento->format('Y-m'),
                'descricao' => $contrato->descricao,
                'valor' => $contrato->valor,
                'data_vencimento' => $vencimento->toDateString(),
                'forma_pagamento' => FormaPagamento::Pix,
                'status' => StatusPagamento::Pendente,
            ]);

            $pagamento->registrarMudancaStatus(
                null,
                StatusPagamento::Pendente,
                "Gerado automaticamente pelo contrato #{$contrato->id}."
            );
        });

        return true;
    }

    /**
     * Primeiro vencimento de um contrato que nunca gerou lancamento: o dia_vencimento
     * dentro do mes de inicio; se ja passou, o mes seguinte.
     */
    private function primeiroVencimento(Contrato $contrato): Carbon
    {
        $inicio = Carbon::parse($contrato->data_inicio);
        $dia = $contrato->dia_vencimento ?? $inicio->day;

        // Dia 31 em mes de 30 dias cai no ultimo dia do mes.
        $vencimento = $inicio->copy()->day(min($dia, $inicio->daysInMonth));

        if ($vencimento->lt($inicio)) {
            $vencimento = $contrato->periodicidade->proximoVencimento($vencimento);
        }

        return $vencimento;
    }

    /**
     * Contrato sem os dados minimos para virar lancamento.
     */
    private function motivoParaIgnorar(Contrato $contrato): ?string
    {
        if (! $contrato->fornecedor->aceitaNovosLancamentos()) {
            return 'Fornecedor inativo.';
        }

        if ($contrato->categoria_id === null && $this->categoriaPadraoServico() === null) {
            return 'Contrato sem categoria e nenhuma categoria de serviço ativa cadastrada.';
        }

        return null;
    }

    /**
     * Fallback quando o contrato nao aponta uma categoria.
     */
    private function categoriaPadraoServico(): ?CategoriaPagamento
    {
        static $categoria = null;
        static $consultada = false;

        if (! $consultada) {
            $categoria = CategoriaPagamento::query()
                ->ativas()
                ->where('tipo', TipoCategoria::Servico)
                ->first();

            $consultada = true;
        }

        return $categoria;
    }
}
