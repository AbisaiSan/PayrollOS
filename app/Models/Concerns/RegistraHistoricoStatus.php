<?php

namespace App\Models\Concerns;

use App\Models\HistoricoStatus;
use BackedEnum;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

/**
 * Trilha de auditoria de mudanca de status (regras 3.6 e 3.10).
 *
 * Compartilhado por Pagamento e Reembolso. Complementa o activitylog: aqui fica o
 * registro explicito de "de qual status para qual", que e o que o financeiro consulta.
 */
trait RegistraHistoricoStatus
{
    public function historicoStatus(): MorphMany
    {
        return $this->morphMany(HistoricoStatus::class, 'historicoavel')->latest('created_at');
    }

    public function registrarMudancaStatus(
        BackedEnum|string|null $anterior,
        BackedEnum|string $novo,
        ?string $observacao = null,
        ?int $usuarioId = null,
    ): HistoricoStatus {
        return $this->historicoStatus()->create([
            'status_anterior' => $anterior instanceof BackedEnum ? $anterior->value : $anterior,
            'status_novo' => $novo instanceof BackedEnum ? $novo->value : $novo,
            'usuario_id' => $usuarioId ?? Auth::id(),
            'observacao' => $observacao,
        ]);
    }
}
