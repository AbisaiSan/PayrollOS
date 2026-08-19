<?php

namespace App\Services;

use App\Enums\StatusContaBancaria;
use App\Exceptions\RegraDeNegocioException;
use App\Models\ContaBancaria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Regra 3.2: no maximo uma conta principal por beneficiario, e contas nunca sao
 * excluidas, apenas inativadas.
 */
class ContaBancariaService
{
    /**
     * @param  Model  $beneficiario  Colaborador ou Fornecedor
     * @param  array<string, mixed>  $dados
     */
    public function criar(Model $beneficiario, array $dados): ContaBancaria
    {
        return DB::transaction(function () use ($beneficiario, $dados) {
            $ehPrincipal = (bool) ($dados['principal'] ?? false);

            // Primeira conta do beneficiario vira principal automaticamente: sem isso,
            // um pagamento lancado logo apos o cadastro ficaria sem destino padrao.
            if (! $beneficiario->contasBancarias()->exists()) {
                $ehPrincipal = true;
            }

            if ($ehPrincipal) {
                $this->desmarcarPrincipais($beneficiario);
            }

            $dados['principal'] = $ehPrincipal;

            return $beneficiario->contasBancarias()->create($dados);
        });
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function atualizar(ContaBancaria $conta, array $dados): ContaBancaria
    {
        return DB::transaction(function () use ($conta, $dados) {
            if (($dados['principal'] ?? false) && ! $conta->principal) {
                $this->desmarcarPrincipais($conta->owner, $conta->id);
            }

            // Tirar o "principal" da unica conta principal deixaria o beneficiario sem
            // destino padrao; para trocar, marque outra conta como principal.
            if (array_key_exists('principal', $dados) && ! $dados['principal'] && $conta->principal) {
                unset($dados['principal']);
            }

            $conta->update($dados);

            return $conta->refresh();
        });
    }

    public function definirPrincipal(ContaBancaria $conta): ContaBancaria
    {
        if (! $conta->estaAtiva()) {
            throw new RegraDeNegocioException(
                'Uma conta inativa não pode ser definida como principal.',
                'principal'
            );
        }

        return DB::transaction(function () use ($conta) {
            $this->desmarcarPrincipais($conta->owner, $conta->id);
            $conta->update(['principal' => true]);

            return $conta->refresh();
        });
    }

    /**
     * Inativa em vez de excluir, preservando o historico dos pagamentos ja
     * lancados para esta conta (regra 3.2).
     */
    public function inativar(ContaBancaria $conta): ContaBancaria
    {
        if ($conta->principal && $conta->owner->contasAtivas()->where('id', '!=', $conta->id)->exists()) {
            throw new RegraDeNegocioException(
                'Defina outra conta como principal antes de inativar esta.',
                'status'
            );
        }

        return DB::transaction(function () use ($conta) {
            $conta->update([
                'status' => StatusContaBancaria::Inativa,
                'principal' => false,
            ]);

            return $conta->refresh();
        });
    }

    public function reativar(ContaBancaria $conta): ContaBancaria
    {
        $conta->update(['status' => StatusContaBancaria::Ativa]);

        return $conta->refresh();
    }

    private function desmarcarPrincipais(Model $beneficiario, ?int $exceto = null): void
    {
        $beneficiario->contasBancarias()
            ->where('principal', true)
            ->when($exceto, fn ($query) => $query->where('id', '!=', $exceto))
            ->update(['principal' => false]);
    }
}
