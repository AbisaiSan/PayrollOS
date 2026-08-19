<?php

namespace App\Rules;

use App\Enums\TipoPessoa;
use App\Support\Documento;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida o documento do fornecedor conforme o tipo de pessoa informado no mesmo
 * formulario. Sem o tipo, aceita qualquer um dos dois.
 */
class CpfOuCnpj implements ValidationRule
{
    public function __construct(private readonly ?TipoPessoa $tipoPessoa = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $documento = is_string($value) ? $value : null;

        $valido = match ($this->tipoPessoa) {
            TipoPessoa::Fisica => Documento::cpfValido($documento),
            TipoPessoa::Juridica => Documento::cnpjValido($documento),
            default => Documento::valido($documento),
        };

        if ($valido) {
            return;
        }

        $fail(match ($this->tipoPessoa) {
            TipoPessoa::Fisica => 'O campo :attribute deve ser um CPF válido.',
            TipoPessoa::Juridica => 'O campo :attribute deve ser um CNPJ válido.',
            default => 'O campo :attribute deve ser um CPF ou CNPJ válido.',
        });
    }
}
