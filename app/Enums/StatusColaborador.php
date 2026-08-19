<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

enum StatusColaborador: string
{
    use TemRotulo;

    case Ativo = 'ativo';
    case Afastado = 'afastado';
    case Desligado = 'desligado';

    public function rotulo(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::Afastado => 'Afastado',
            self::Desligado => 'Desligado',
        };
    }

    /**
     * Colaborador desligado nao recebe novos lancamentos de folha (regra 3.1).
     */
    public function aceitaNovosLancamentos(): bool
    {
        return $this !== self::Desligado;
    }
}
