<?php

namespace Database\Factories;

use App\Enums\TipoCategoria;
use App\Models\CategoriaPagamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoriaPagamento>
 */
class CategoriaPagamentoFactory extends Factory
{
    protected $model = CategoriaPagamento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->words(2, true),
            'tipo' => fake()->randomElement(TipoCategoria::cases()),
            'ativo' => true,
        ];
    }

    public function doTipo(TipoCategoria $tipo): static
    {
        return $this->state(fn () => ['tipo' => $tipo]);
    }
}
