<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

enum TipoFornecedor: string
{
    use TemRotulo;

    case Produto = 'produto';
    case Servico = 'servico';
    case Ambos = 'ambos';

    public function rotulo(): string
    {
        return match ($this) {
            self::Produto => 'Fornecedor de produto',
            self::Servico => 'Prestador de serviço',
            self::Ambos => 'Ambos',
        };
    }
}
