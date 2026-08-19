<?php

namespace Database\Factories;

use App\Enums\StatusColaborador;
use App\Enums\TipoContratacao;
use App\Models\Colaborador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Colaborador>
 */
class ColaboradorFactory extends Factory
{
    protected $model = Colaborador::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'cpf' => $this->cpfValido(),
            'cargo' => fake()->jobTitle(),
            'departamento' => fake()->randomElement(['Financeiro', 'Tecnologia', 'Comercial', 'Operações']),
            'tipo_contrato' => fake()->randomElement(TipoContratacao::cases()),
            'data_admissao' => fake()->dateTimeBetween('-5 years', '-1 month'),
            'salario_base' => fake()->randomFloat(2, 1500, 20000),
            'email' => fake()->unique()->safeEmail(),
            'telefone' => fake()->numerify('119########'),
            'status' => StatusColaborador::Ativo,
        ];
    }

    public function desligado(): static
    {
        return $this->state(fn () => [
            'status' => StatusColaborador::Desligado,
            'data_desligamento' => now()->subDays(10),
        ]);
    }

    /**
     * Gera um CPF que passa na validacao de digito verificador, senao os testes
     * de cadastro esbarram na propria regra que deveriam exercitar.
     */
    private function cpfValido(): string
    {
        $base = '';

        for ($i = 0; $i < 9; $i++) {
            $base .= random_int(0, 9);
        }

        foreach ([9, 10] as $posicao) {
            $soma = 0;

            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $base[$i] * (($posicao + 1) - $i);
            }

            $base .= ((10 * $soma) % 11) % 10;
        }

        return $base;
    }
}
