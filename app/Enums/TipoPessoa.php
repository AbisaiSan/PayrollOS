<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

enum TipoPessoa: string
{
    use TemRotulo;

    case Fisica = 'pf';
    case Juridica = 'pj';

    public function rotulo(): string
    {
        return match ($this) {
            self::Fisica => 'Pessoa Física',
            self::Juridica => 'Pessoa Jurídica',
        };
    }

    /**
     * Quantidade de digitos do documento correspondente (CPF ou CNPJ).
     */
    public function digitosDocumento(): int
    {
        return match ($this) {
            self::Fisica => 11,
            self::Juridica => 14,
        };
    }
}
