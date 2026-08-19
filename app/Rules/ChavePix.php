<?php

namespace App\Rules;

use App\Enums\TipoChavePix;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida somente o formato da chave. Consultar o DICT para confirmar que a chave
 * existe e pertence ao titular esta fora do escopo desta versao (secao 7 do plano).
 */
class ChavePix implements ValidationRule
{
    public function __construct(private readonly ?TipoChavePix $tipo) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->tipo === null) {
            $fail('Informe o tipo da chave Pix antes da chave.');

            return;
        }

        if (! is_string($value) || ! $this->tipo->formatoValido($value)) {
            $fail("O campo :attribute não é uma chave Pix válida do tipo {$this->tipo->rotulo()}.");
        }
    }
}
