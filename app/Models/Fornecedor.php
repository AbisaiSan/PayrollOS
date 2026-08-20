<?php

namespace App\Models;

use App\Enums\StatusFornecedor;
use App\Enums\TipoFornecedor;
use App\Enums\TipoPessoa;
use App\Models\Concerns\TemContasBancarias;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Fornecedor extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use TemContasBancarias;

    protected $table = 'fornecedores';

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'ativo'];

    protected $fillable = [
        'tipo_pessoa',
        'razao_social',
        'nome_fantasia',
        'documento',
        'tipo_fornecedor',
        'email',
        'telefone',
        'endereco',
        'status',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'tipo_pessoa' => TipoPessoa::class,
            'tipo_fornecedor' => TipoFornecedor::class,
            'status' => StatusFornecedor::class,
        ];
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class);
    }

    public function pagamentos(): MorphMany
    {
        return $this->morphMany(Pagamento::class, 'payable');
    }

    /**
     * Nome de exibicao: fantasia quando houver, senao a razao social.
     */
    public function getNomeExibicaoAttribute(): string
    {
        return $this->nome_fantasia ?: $this->razao_social;
    }

    /**
     * Regra 3.3: inativo mantem historico, mas nao recebe novos lancamentos.
     */
    public function aceitaNovosLancamentos(): bool
    {
        return $this->status->aceitaNovosLancamentos();
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('status', StatusFornecedor::Ativo);
    }

    public function scopeBusca(Builder $query, ?string $termo): Builder
    {
        if (blank($termo)) {
            return $query;
        }

        $termo = trim($termo);
        $somenteDigitos = preg_replace('/\D/', '', $termo);

        // Ver a nota em Colaborador::scopeBusca: LIKE no PostgreSQL e sensivel a caixa.
        $padrao = '%'.mb_strtolower($termo).'%';

        return $query->where(function (Builder $q) use ($padrao, $somenteDigitos) {
            $q->whereRaw('LOWER(razao_social) LIKE ?', [$padrao])
                ->orWhereRaw('LOWER(nome_fantasia) LIKE ?', [$padrao])
                ->orWhereRaw('LOWER(email) LIKE ?', [$padrao]);

            if ($somenteDigitos !== '') {
                $q->orWhere('documento', 'like', "%{$somenteDigitos}%");
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['razao_social', 'nome_fantasia', 'documento', 'tipo_pessoa', 'tipo_fornecedor', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('fornecedor');
    }
}
