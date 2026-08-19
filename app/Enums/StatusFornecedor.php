<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

enum StatusFornecedor: string
{
    use TemRotulo;

    case Ativo = 'ativo';
    case Inativo = 'inativo';

    public function rotulo(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::Inativo => 'Inativo',
        };
    }

    /**
     * Fornecedor inativo mantem historico, mas nao recebe novos lancamentos (regra 3.3).
     */
    public function aceitaNovosLancamentos(): bool
    {
        return $this === self::Ativo;
    }
}
