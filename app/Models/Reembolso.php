<?php

namespace App\Models;

use App\Enums\CategoriaReembolso;
use App\Enums\StatusReembolso;
use App\Models\Concerns\RegistraHistoricoStatus;
use App\Models\Concerns\TemAnexos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Reembolso de despesa de colaborador (regra 3.7).
 */
class Reembolso extends Model
{
    use HasFactory;
    use LogsActivity;
    use RegistraHistoricoStatus;
    use SoftDeletes;
    use TemAnexos;

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'pendente'];

    protected $fillable = [
        'colaborador_id',
        'conta_bancaria_id',
        'descricao',
        'categoria',
        'valor',
        'data_solicitacao',
        'data_pagamento',
        'status',
        'solicitado_por',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'categoria' => CategoriaReembolso::class,
            'status' => StatusReembolso::class,
            'valor' => 'decimal:2',
            'data_solicitacao' => 'date',
            'data_pagamento' => 'date',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function contaBancaria(): BelongsTo
    {
        return $this->belongsTo(ContaBancaria::class);
    }

    public function solicitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function scopeEmAberto(Builder $query): Builder
    {
        return $query->whereIn('status', [StatusReembolso::Pendente, StatusReembolso::Aprovado]);
    }

    public function scopeNoPeriodo(Builder $query, ?string $inicio, ?string $fim): Builder
    {
        return $query
            ->when($inicio, fn (Builder $q) => $q->whereDate('data_solicitacao', '>=', $inicio))
            ->when($fim, fn (Builder $q) => $q->whereDate('data_solicitacao', '<=', $fim));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['valor', 'categoria', 'status', 'data_pagamento'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('reembolso');
    }
}
