<?php

namespace Database\Factories;

use App\Enums\StatusFornecedor;
use App\Enums\TipoFornecedor;
use App\Enums\TipoPessoa;
use App\Models\Fornecedor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fornecedor>
 */
class FornecedorFactory extends Factory
{
    protected $model = Fornecedor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo_pessoa' => TipoPessoa::Juridica,
            'razao_social' => fake()->company().' LTDA',
            'nome_fantasia' => fake()->company(),
            'documento' => $this->cnpjValido(),
            'tipo_fornecedor' => fake()->randomElement(TipoFornecedor::cases()),
            'email' => fake()->unique()->companyEmail(),
            'telefone' => fake()->numerify('113########'),
            'status' => StatusFornecedor::Ativo,
        ];
    }

    public function inativo(): static
    {
        return $this->state(fn () => ['status' => StatusFornecedor::Inativo]);
    }

    private function cnpjValido(): string
    {
        $base = '';

        for ($i = 0; $i < 12; $i++) {
            $base .= random_int(0, 9);
        }

        for ($j = 0; $j < 2; $j++) {
            $peso = strlen($base) - 7;
            $soma = 0;

            foreach (str_split($base) as $numero) {
                $soma += (int) $numero * $peso;
                $peso = $peso === 2 ? 9 : $peso - 1;
            }

            $resto = $soma % 11;
            $base .= $resto < 2 ? 0 : 11 - $resto;
        }

        return $base;
    }
}
