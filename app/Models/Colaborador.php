<?php

namespace App\Models;

use App\Enums\StatusColaborador;
use App\Enums\TipoContratacao;
use App\Models\Concerns\TemContasBancarias;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Colaborador extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use TemContasBancarias;

    protected $table = 'colaboradores';

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'ativo'];

    protected $fillable = [
        'nome',
        'cpf',
        'cargo',
        'departamento',
        'tipo_contrato',
        'data_admissao',
        'data_desligamento',
        'salario_base',
        'email',
        'telefone',
        'status',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'tipo_contrato' => TipoContratacao::class,
            'status' => StatusColaborador::class,
            'data_admissao' => 'date',
            'data_desligamento' => 'date',
            'salario_base' => 'decimal:2',
        ];
    }

    public function pagamentos(): MorphMany
    {
        return $this->morphMany(Pagamento::class, 'payable');
    }

    public function reembolsos(): HasMany
    {
        return $this->hasMany(Reembolso::class);
    }

    /**
     * Usuario de acesso ao sistema, quando existir (regra 3.9).
     */
    public function usuario(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Regra 3.1: desligado nao recebe novos lancamentos de folha, mas o historico fica.
     */
    public function aceitaNovosLancamentos(): bool
    {
        return $this->status->aceitaNovosLancamentos();
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('status', StatusColaborador::Ativo);
    }

    public function scopeBusca(Builder $query, ?string $termo): Builder
    {
        if (blank($termo)) {
            return $query;
        }

        $termo = trim($termo);
        $somenteDigitos = preg_replace('/\D/', '', $termo);

        return $query->where(function (Builder $q) use ($termo, $somenteDigitos) {
            $q->where('nome', 'like', "%{$termo}%")
                ->orWhere('email', 'like', "%{$termo}%")
                ->orWhere('cargo', 'like', "%{$termo}%");

            if ($somenteDigitos !== '') {
                $q->orWhere('cpf', 'like', "%{$somenteDigitos}%");
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nome', 'cpf', 'cargo', 'departamento', 'tipo_contrato', 'salario_base', 'status', 'data_desligamento'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('colaborador');
    }
}
