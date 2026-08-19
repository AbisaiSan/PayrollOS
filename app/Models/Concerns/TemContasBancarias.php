<?php

namespace App\Models\Concerns;

use App\Enums\StatusContaBancaria;
use App\Models\ContaBancaria;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Compartilhado por Colaborador e Fornecedor (regra 3.2).
 */
trait TemContasBancarias
{
    public function contasBancarias(): MorphMany
    {
        return $this->morphMany(ContaBancaria::class, 'owner');
    }

    /**
     * Conta principal: destino padrao do pagamento.
     */
    public function contaPrincipal(): MorphOne
    {
        return $this->morphOne(ContaBancaria::class, 'owner')->where('principal', true);
    }

    public function contasAtivas(): MorphMany
    {
        return $this->contasBancarias()->where('status', StatusContaBancaria::Ativa);
    }
}
