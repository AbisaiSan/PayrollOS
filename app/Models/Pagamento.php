<?php

namespace App\Models;

use App\Enums\FormaPagamento;
use App\Enums\StatusPagamento;
use App\Models\Concerns\RegistraHistoricoStatus;
use App\Models\Concerns\TemAnexos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Modulo central (regra 3.6).
 *
 * O sistema nao executa o pagamento. Todo avanco de status e uma confirmacao
 * manual, e passa por PagamentoService para gravar o historico de auditoria.
 */
class Pagamento extends Model
{
    use HasFactory;
    use LogsActivity;
    use RegistraHistoricoStatus;
    use SoftDeletes;
    use TemAnexos;

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'pendente'];

    protected $fillable = [
        'payable_type',
        'payable_id',
        'categoria_id',
        'contrato_id',
        'conta_bancaria_id',
        'competencia',
        'descricao',
        'valor',
        'data_vencimento',
        'data_pagamento',
        'forma_pagamento',
        'status',
        'criado_por',
        'atualizado_por',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'forma_pagamento' => FormaPagamento::class,
            'status' => StatusPagamento::class,
            'valor' => 'decimal:2',
            'data_vencimento' => 'date',
            'data_pagamento' => 'date',
        ];
    }

    /**
     * Colaborador ou Fornecedor.
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaPagamento::class, 'categoria_id');
    }

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    public function contaBancaria(): BelongsTo
    {
        return $this->belongsTo(ContaBancaria::class);
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function atualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por');
    }

    /**
     * Vencido sem confirmacao. A rotina diaria usa isto para promover o status
     * para Atrasado; a tela usa para destacar a linha antes de a rotina rodar.
     */
    public function estaVencido(): bool
    {
        return $this->status->estaEmAberto()
            && $this->status !== StatusPagamento::Pago
            && $this->data_vencimento->isPast()
            && ! $this->data_vencimento->isToday();
    }

    public function scopeEmAberto(Builder $query): Builder
    {
        return $query->whereIn('status', StatusPagamento::valoresEmAberto());
    }

    public function scopeVencendoAte(Builder $query, \DateTimeInterface $data): Builder
    {
        return $query->whereDate('data_vencimento', '<=', $data);
    }

    public function scopeNoPeriodo(Builder $query, ?string $inicio, ?string $fim): Builder
    {
        return $query
            ->when($inicio, fn (Builder $q) => $q->whereDate('data_vencimento', '>=', $inicio))
            ->when($fim, fn (Builder $q) => $q->whereDate('data_vencimento', '<=', $fim));
    }

    public function scopeDaCompetencia(Builder $query, string $competencia): Builder
    {
        return $query->where('competencia', $competencia);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['valor', 'data_vencimento', 'data_pagamento', 'status', 'forma_pagamento', 'categoria_id', 'conta_bancaria_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('pagamento');
    }
}
