<?php

namespace Database\Factories;

use App\Enums\Periodicidade;
use App\Enums\StatusContrato;
use App\Enums\TipoContrato;
use App\Models\Contrato;
use App\Models\Fornecedor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contrato>
 */
class ContratoFactory extends Factory
{
    protected $model = Contrato::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fornecedor_id' => Fornecedor::factory(),
            'descricao' => fake()->randomElement(['Contabilidade', 'Aluguel', 'Licença de software']),
            'tipo' => TipoContrato::Recorrente,
            'valor' => fake()->randomFloat(2, 500, 8000),
            'periodicidade' => Periodicidade::Mensal,
            'dia_vencimento' => fake()->numberBetween(1, 28),
            'data_inicio' => now()->subMonths(3)->startOfMonth(),
            'status' => StatusContrato::Ativo,
        ];
    }

    public function pontual(): static
    {
        return $this->state(fn () => [
            'tipo' => TipoContrato::Pontual,
            'periodicidade' => null,
            'dia_vencimento' => null,
        ]);
    }

    public function encerrado(): static
    {
        return $this->state(fn () => ['status' => StatusContrato::Encerrado]);
    }
}
