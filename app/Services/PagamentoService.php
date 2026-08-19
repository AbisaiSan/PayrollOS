<?php

namespace App\Services;

use App\Enums\StatusPagamento;
use App\Exceptions\RegraDeNegocioException;
use App\Models\ContaBancaria;
use App\Models\Pagamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Modulo central (regra 3.6).
 *
 * O sistema nao executa pagamento nenhum: aqui so se registra a intencao e a
 * confirmacao manual do usuario, sempre gravando o historico de auditoria.
 */
class PagamentoService
{
    /**
     * @param  Model  $beneficiario  Colaborador ou Fornecedor
     * @param  array<string, mixed>  $dados
     */
    public function lancar(Model $beneficiario, array $dados): Pagamento
    {
        $this->garantirQueBeneficiarioAceitaLancamento($beneficiario);

        if (isset($dados['conta_bancaria_id'])) {
            $this->garantirQueContaPertenceAoBeneficiario($beneficiario, (int) $dados['conta_bancaria_id']);
        }

        return DB::transaction(function () use ($beneficiario, $dados) {
            $status = StatusPagamento::from($dados['status'] ?? StatusPagamento::Pendente->value);

            $pagamento = $beneficiario->pagamentos()->create([
                ...$dados,
                'status' => $status,
                'criado_por' => Auth::id(),
                'atualizado_por' => Auth::id(),
            ]);

            $pagamento->registrarMudancaStatus(null, $status, 'Lançamento criado.');

            return $pagamento;
        });
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function atualizar(Pagamento $pagamento, array $dados): Pagamento
    {
        if ($pagamento->status === StatusPagamento::Cancelado) {
            throw new RegraDeNegocioException('Um pagamento cancelado não pode ser editado.');
        }

        // Status tem porta propria (alterarStatus), que grava o historico.
        unset($dados['status']);

        if (isset($dados['conta_bancaria_id'])) {
            $this->garantirQueContaPertenceAoBeneficiario($pagamento->payable, (int) $dados['conta_bancaria_id']);
        }

        $pagamento->update([...$dados, 'atualizado_por' => Auth::id()]);

        return $pagamento->refresh();
    }

    /**
     * Unica porta de entrada para mudanca de status: valida a transicao e grava
     * o historico de auditoria (regra 3.6).
     */
    public function alterarStatus(
        Pagamento $pagamento,
        StatusPagamento $novoStatus,
        ?string $observacao = null,
        ?Carbon $dataPagamento = null,
    ): Pagamento {
        $statusAtual = $pagamento->status;

        if ($statusAtual === $novoStatus) {
            return $pagamento;
        }

        if (! $statusAtual->podeIrPara($novoStatus)) {
            throw new RegraDeNegocioException(
                "Não é possível mudar de {$statusAtual->rotulo()} para {$novoStatus->rotulo()}.",
                'status'
            );
        }

        return DB::transaction(function () use ($pagamento, $statusAtual, $novoStatus, $observacao, $dataPagamento) {
            $atributos = [
                'status' => $novoStatus,
                'atualizado_por' => Auth::id(),
            ];

            if ($novoStatus === StatusPagamento::Pago) {
                // Confirmacao manual sempre carrega a data em que o pagamento saiu.
                $atributos['data_pagamento'] = $dataPagamento ?? now()->toDateString();
            }

            // Sair de Pago limpa a data, senao o relatorio contaria um pagamento
            // que voltou a ficar em aberto como se tivesse sido quitado.
            if ($statusAtual === StatusPagamento::Pago && $novoStatus !== StatusPagamento::Pago) {
                $atributos['data_pagamento'] = null;
            }

            $pagamento->update($atributos);
            $pagamento->registrarMudancaStatus($statusAtual, $novoStatus, $observacao);

            return $pagamento->refresh();
        });
    }

    public function confirmarPagamento(Pagamento $pagamento, ?Carbon $dataPagamento = null, ?string $observacao = null): Pagamento
    {
        return $this->alterarStatus($pagamento, StatusPagamento::Pago, $observacao, $dataPagamento);
    }

    public function cancelar(Pagamento $pagamento, ?string $motivo = null): Pagamento
    {
        return $this->alterarStatus($pagamento, StatusPagamento::Cancelado, $motivo);
    }

    /**
     * Promove para Atrasado todo lancamento em aberto cujo vencimento ja passou.
     * Chamado pela rotina diaria (MarcarPagamentosAtrasados).
     *
     * @return int quantidade de pagamentos promovidos
     */
    public function marcarAtrasados(?Carbon $referencia = null): int
    {
        $referencia = $referencia ?? now();

        $pagamentos = Pagamento::query()
            ->whereIn('status', [StatusPagamento::Pendente->value, StatusPagamento::Agendado->value])
            ->whereDate('data_vencimento', '<', $referencia->toDateString())
            ->get();

        foreach ($pagamentos as $pagamento) {
            $this->alterarStatus(
                $pagamento,
                StatusPagamento::Atrasado,
                'Marcado automaticamente: vencimento em '.$pagamento->data_vencimento->format('d/m/Y').'.'
            );
        }

        return $pagamentos->count();
    }

    private function garantirQueBeneficiarioAceitaLancamento(Model $beneficiario): void
    {
        if (method_exists($beneficiario, 'aceitaNovosLancamentos') && ! $beneficiario->aceitaNovosLancamentos()) {
            throw new RegraDeNegocioException(
                'Este beneficiário está inativo ou desligado e não aceita novos lançamentos.',
                'payable_id'
            );
        }
    }

    /**
     * Evita lancar um pagamento na conta de outro beneficiario, que e o tipo de
     * erro que so aparece depois do dinheiro sair.
     */
    private function garantirQueContaPertenceAoBeneficiario(Model $beneficiario, int $contaBancariaId): void
    {
        $conta = ContaBancaria::find($contaBancariaId);

        if (! $conta
            || $conta->owner_type !== $beneficiario->getMorphClass()
            || $conta->owner_id !== $beneficiario->getKey()
        ) {
            throw new RegraDeNegocioException(
                'A conta de destino não pertence a este beneficiário.',
                'conta_bancaria_id'
            );
        }

        if (! $conta->estaAtiva()) {
            throw new RegraDeNegocioException(
                'A conta de destino está inativa.',
                'conta_bancaria_id'
            );
        }
    }
}
