<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

enum CategoriaReembolso: string
{
    use TemRotulo;

    case Viagem = 'viagem';
    case Alimentacao = 'alimentacao';
    case Material = 'material';
    case Transporte = 'transporte';
    case Outro = 'outro';

    public function rotulo(): string
    {
        return match ($this) {
            self::Viagem => 'Viagem',
            self::Alimentacao => 'Alimentação',
            self::Material => 'Material',
            self::Transporte => 'Transporte',
            self::Outro => 'Outro',
        };
    }
}
