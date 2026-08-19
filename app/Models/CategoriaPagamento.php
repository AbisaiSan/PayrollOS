<?php

namespace App\Models;

use App\Enums\TipoCategoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Categorias sao cadastraveis pelo financeiro, nao fixas em codigo (regra 3.5).
 * O enum TipoCategoria e a classificacao por tras, usada nos relatorios.
 */
class CategoriaPagamento extends Model
{
    use HasFactory;

    protected $table = 'categorias_pagamento';

    /** @var array<string, mixed> */
    protected $attributes = ['ativo' => true];

    protected $fillable = [
        'nome',
        'tipo',
        'descricao',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoCategoria::class,
            'ativo' => 'boolean',
        ];
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class, 'categoria_id');
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class, 'categoria_id');
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }
}
