<?php

namespace App\Models\Concerns;

use App\Models\Anexo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Compartilhado por Pagamento, Reembolso e Contrato (regra 3.8).
 */
trait TemAnexos
{
    public function anexos(): MorphMany
    {
        return $this->morphMany(Anexo::class, 'anexavel')->latest();
    }
}
