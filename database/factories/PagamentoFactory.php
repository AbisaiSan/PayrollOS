<?php

namespace Database\Factories;

use App\Enums\FormaPagamento;
use App\Enums\StatusPagamento;
use App\Models\CategoriaPagamento;
use App\Models\Colaborador;
use App\Models\Pagamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pagamento>
 */
class PagamentoFactory extends Factory
{
    protected $model = Pagamento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payable_type' => 'colaborador',
            'payable_id' => Colaborador::factory(),
            'categoria_id' => CategoriaPagamento::factory(),
            'descricao' => fake()->sentence(3),
            'valor' => fake()->randomFloat(2, 100, 15000),
            'data_vencimento' => fake()->dateTimeBetween('now', '+30 days'),
            'forma_pagamento' => FormaPagamento::Pix,
            'status' => StatusPagamento::Pendente,
        ];
    }

    public function comStatus(StatusPagamento $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'data_pagamento' => $status === StatusPagamento::Pago ? now()->toDateString() : null,
        ]);
    }

    public function vencido(): static
    {
        return $this->state(fn () => [
            'data_vencimento' => now()->subDays(5)->toDateString(),
        ]);
    }
}
