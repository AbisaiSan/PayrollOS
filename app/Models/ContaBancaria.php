<?php

namespace App\Models;

use App\Enums\StatusContaBancaria;
use App\Enums\TipoChavePix;
use App\Enums\TipoConta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Conta bancaria / chave Pix de um beneficiario (regra 3.2).
 *
 * Nao ha exclusao: contas saem de circulacao via status inativa, para preservar
 * o vinculo com os pagamentos ja lancados.
 */
class ContaBancaria extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'contas_bancarias';

    /**
     * Espelha os defaults da migration: sem isto, uma conta recem-criada volta
     * sem status em memoria e as checagens do service leem null.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'ativa',
        'principal' => false,
    ];

    protected $fillable = [
        'banco',
        'codigo_banco',
        'agencia',
        'conta',
        'digito',
        'tipo_conta',
        'titular_nome',
        'titular_documento',
        'chave_pix',
        'tipo_chave_pix',
        'principal',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tipo_conta' => TipoConta::class,
            'tipo_chave_pix' => TipoChavePix::class,
            'status' => StatusContaBancaria::class,
            'principal' => 'boolean',
        ];
    }

    /**
     * Colaborador ou Fornecedor.
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class);
    }

    public function estaAtiva(): bool
    {
        return $this->status === StatusContaBancaria::Ativa;
    }

    /**
     * Resumo legivel para grids e selects: "Itau, Ag. 1234, C/C 56789-0".
     */
    public function getResumoAttribute(): string
    {
        $conta = $this->digito ? "{$this->conta}-{$this->digito}" : $this->conta;
        $tipo = $this->tipo_conta === TipoConta::Poupanca ? 'Poup.' : 'C/C';

        return "{$this->banco}, Ag. {$this->agencia}, {$tipo} {$conta}";
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('status', StatusContaBancaria::Ativa);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['banco', 'agencia', 'conta', 'digito', 'chave_pix', 'tipo_chave_pix', 'principal', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('conta_bancaria');
    }
}
