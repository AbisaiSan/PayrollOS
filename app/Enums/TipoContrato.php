<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

enum TipoContrato: string
{
    use TemRotulo;

    case Pontual = 'pontual';
    case Recorrente = 'recorrente';

    public function rotulo(): string
    {
        return match ($this) {
            self::Pontual => 'Pontual',
            self::Recorrente => 'Recorrente',
        };
    }
}
