<?php

namespace Database\Factories;

use App\Enums\CategoriaReembolso;
use App\Enums\StatusReembolso;
use App\Models\Colaborador;
use App\Models\Reembolso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reembolso>
 */
class ReembolsoFactory extends Factory
{
    protected $model = Reembolso::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'colaborador_id' => Colaborador::factory(),
            'descricao' => fake()->sentence(3),
            'categoria' => fake()->randomElement(CategoriaReembolso::cases()),
            'valor' => fake()->randomFloat(2, 20, 3000),
            'data_solicitacao' => now()->subDays(fake()->numberBetween(0, 20))->toDateString(),
            'status' => StatusReembolso::Pendente,
        ];
    }

    public function comStatus(StatusReembolso $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
