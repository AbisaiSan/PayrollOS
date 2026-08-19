<?php

namespace App\Services;

use App\Enums\StatusReembolso;
use App\Exceptions\RegraDeNegocioException;
use App\Models\Colaborador;
use App\Models\Reembolso;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Regra 3.7. Hoje nao ha aprovacao formal: qualquer usuario com a permissao pode
 * aprovar. Quando a regra existir, ela entra nas policies, nao aqui.
 */
class ReembolsoService
{
    /**
     * @param  array<string, mixed>  $dados
     */
    public function solicitar(Colaborador $colaborador, array $dados): Reembolso
    {
        return DB::transaction(function () use ($colaborador, $dados) {
            $reembolso = $colaborador->reembolsos()->create([
                ...$dados,
                'status' => StatusReembolso::Pendente,
                'data_solicitacao' => $dados['data_solicitacao'] ?? now()->toDateString(),
                'solicitado_por' => Auth::id(),
            ]);

            $reembolso->registrarMudancaStatus(null, StatusReembolso::Pendente, 'Solicitação registrada.');

            return $reembolso;
        });
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function atualizar(Reembolso $reembolso, array $dados): Reembolso
    {
        if ($reembolso->status === StatusReembolso::Pago) {
            throw new RegraDeNegocioException('Um reembolso já pago não pode ser editado.');
        }

        unset($dados['status']);

        $reembolso->update($dados);

        return $reembolso->refresh();
    }

    public function alterarStatus(
        Reembolso $reembolso,
        StatusReembolso $novoStatus,
        ?string $observacao = null,
        ?Carbon $dataPagamento = null,
    ): Reembolso {
        $statusAtual = $reembolso->status;

        if ($statusAtual === $novoStatus) {
            return $reembolso;
        }

        if (! $statusAtual->podeIrPara($novoStatus)) {
            throw new RegraDeNegocioException(
                "Não é possível mudar de {$statusAtual->rotulo()} para {$novoStatus->rotulo()}.",
                'status'
            );
        }

        return DB::transaction(function () use ($reembolso, $statusAtual, $novoStatus, $observacao, $dataPagamento) {
            $atributos = ['status' => $novoStatus];

            if ($novoStatus === StatusReembolso::Pago) {
                $atributos['data_pagamento'] = $dataPagamento ?? now()->toDateString();
            }

            if ($statusAtual === StatusReembolso::Pago) {
                $atributos['data_pagamento'] = null;
            }

            $reembolso->update($atributos);
            $reembolso->registrarMudancaStatus($statusAtual, $novoStatus, $observacao);

            return $reembolso->refresh();
        });
    }

    public function aprovar(Reembolso $reembolso, ?string $observacao = null): Reembolso
    {
        return $this->alterarStatus($reembolso, StatusReembolso::Aprovado, $observacao);
    }

    public function rejeitar(Reembolso $reembolso, string $motivo): Reembolso
    {
        return $this->alterarStatus($reembolso, StatusReembolso::Rejeitado, $motivo);
    }

    public function confirmarPagamento(Reembolso $reembolso, ?Carbon $dataPagamento = null): Reembolso
    {
        return $this->alterarStatus($reembolso, StatusReembolso::Pago, null, $dataPagamento);
    }
}
