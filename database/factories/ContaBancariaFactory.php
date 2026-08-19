<?php

namespace Database\Factories;

use App\Enums\StatusContaBancaria;
use App\Enums\TipoConta;
use App\Models\Colaborador;
use App\Models\ContaBancaria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContaBancaria>
 */
class ContaBancariaFactory extends Factory
{
    protected $model = ContaBancaria::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_type' => 'colaborador',
            'owner_id' => Colaborador::factory(),
            'banco' => fake()->randomElement(['Itaú', 'Bradesco', 'Banco do Brasil', 'Nubank', 'Santander']),
            'codigo_banco' => fake()->numerify('###'),
            'agencia' => fake()->numerify('####'),
            'conta' => fake()->numerify('#####'),
            'digito' => (string) fake()->numberBetween(0, 9),
            'tipo_conta' => TipoConta::Corrente,
            'titular_nome' => fake()->name(),
            'titular_documento' => fake()->numerify('###########'),
            'principal' => false,
            'status' => StatusContaBancaria::Ativa,
        ];
    }

    public function principal(): static
    {
        return $this->state(fn () => ['principal' => true]);
    }

    public function inativa(): static
    {
        return $this->state(fn () => [
            'status' => StatusContaBancaria::Inativa,
            'principal' => false,
        ]);
    }
}
