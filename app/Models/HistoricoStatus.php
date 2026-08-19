<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Registro imutavel de mudanca de status (regras 3.6 e 3.10).
 *
 * Nao tem updated_at nem update: se algo foi registrado errado, o certo e
 * registrar a correcao como um novo evento.
 */
class HistoricoStatus extends Model
{
    protected $table = 'historico_status';

    public const UPDATED_AT = null;

    protected $fillable = [
        'status_anterior',
        'status_novo',
        'usuario_id',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * Pagamento ou Reembolso.
     */
    public function historicoavel(): MorphTo
    {
        return $this->morphTo();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
