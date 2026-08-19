<?php

namespace App\Models;

use App\Enums\Periodicidade;
use App\Enums\StatusContrato;
use App\Enums\TipoContrato;
use App\Models\Concerns\TemAnexos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Servico contratado, normalmente recorrente (regra 3.4).
 *
 * Pagamentos pontuais nao precisam de contrato: sao lancados direto no fornecedor.
 */
class Contrato extends Model
{
    use HasFactory;
    use LogsActivity;
    use TemAnexos;

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'ativo'];

    protected $fillable = [
        'fornecedor_id',
        'categoria_id',
        'conta_bancaria_id',
        'descricao',
        'tipo',
        'valor',
        'periodicidade',
        'dia_vencimento',
        'data_inicio',
        'data_fim',
        'proximo_vencimento',
        'status',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoContrato::class,
            'periodicidade' => Periodicidade::class,
            'status' => StatusContrato::class,
            'valor' => 'decimal:2',
            'dia_vencimento' => 'integer',
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'proximo_vencimento' => 'date',
        ];
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaPagamento::class, 'categoria_id');
    }

    public function contaBancaria(): BelongsTo
    {
        return $this->belongsTo(ContaBancaria::class);
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class);
    }

    /**
     * Regra 3.4: so contrato recorrente e ativo alimenta a rotina de geracao.
     */
    public function geraLancamentosAutomaticos(): bool
    {
        return $this->tipo === TipoContrato::Recorrente
            && $this->status->geraLancamentos()
            && $this->periodicidade !== null;
    }

    /**
     * Contrato vencido pela data_fim nao deve gerar novos lancamentos.
     */
    public function vigenteEm(\DateTimeInterface $data): bool
    {
        if ($this->data_inicio->gt($data)) {
            return false;
        }

        return $this->data_fim === null || $this->data_fim->gte($data);
    }

    public function scopeRecorrentesAtivos(Builder $query): Builder
    {
        return $query->where('tipo', TipoContrato::Recorrente)
            ->where('status', StatusContrato::Ativo);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['descricao', 'tipo', 'valor', 'periodicidade', 'dia_vencimento', 'data_fim', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('contrato');
    }
}
