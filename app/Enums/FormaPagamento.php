<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

enum FormaPagamento: string
{
    use TemRotulo;

    case Pix = 'pix';
    case Ted = 'ted';
    case Boleto = 'boleto';
    case Dinheiro = 'dinheiro';
    case Outro = 'outro';

    public function rotulo(): string
    {
        return match ($this) {
            self::Pix => 'Pix',
            self::Ted => 'TED',
            self::Boleto => 'Boleto',
            self::Dinheiro => 'Dinheiro',
            self::Outro => 'Outro',
        };
    }

    /**
     * Formas que dependem de uma conta bancaria de destino cadastrada.
     */
    public function exigeContaDestino(): bool
    {
        return in_array($this, [self::Pix, self::Ted], true);
    }
}
