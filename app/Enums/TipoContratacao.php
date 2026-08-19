<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

enum TipoContratacao: string
{
    use TemRotulo;

    case Clt = 'clt';
    case Pj = 'pj';
    case Estagio = 'estagio';
    case Autonomo = 'autonomo';

    public function rotulo(): string
    {
        return match ($this) {
            self::Clt => 'CLT',
            self::Pj => 'PJ',
            self::Estagio => 'Estágio',
            self::Autonomo => 'Autônomo',
        };
    }
}
