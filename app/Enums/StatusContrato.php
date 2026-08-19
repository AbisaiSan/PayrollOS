<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

enum StatusContrato: string
{
    use TemRotulo;

    case Ativo = 'ativo';
    case Suspenso = 'suspenso';
    case Encerrado = 'encerrado';

    public function rotulo(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::Suspenso => 'Suspenso',
            self::Encerrado => 'Encerrado',
        };
    }

    /**
     * Contrato suspenso ou encerrado nao gera novos lancamentos automaticos (regra 3.4).
     */
    public function geraLancamentos(): bool
    {
        return $this === self::Ativo;
    }
}
