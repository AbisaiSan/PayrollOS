<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

enum StatusContaBancaria: string
{
    use TemRotulo;

    case Ativa = 'ativa';
    case Inativa = 'inativa';

    public function rotulo(): string
    {
        return match ($this) {
            self::Ativa => 'Ativa',
            self::Inativa => 'Inativa',
        };
    }
}
