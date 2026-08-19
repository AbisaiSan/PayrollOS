<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

enum TipoConta: string
{
    use TemRotulo;

    case Corrente = 'corrente';
    case Poupanca = 'poupanca';

    public function rotulo(): string
    {
        return match ($this) {
            self::Corrente => 'Conta corrente',
            self::Poupanca => 'Conta poupança',
        };
    }
}
