<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

/**
 * Classificacao fixa por tras das categorias cadastraveis (regra 3.5).
 *
 * O financeiro cria e renomeia categorias sem deploy; o tipo abaixo e o que os
 * relatorios e regras de negocio usam para agrupar.
 */
enum TipoCategoria: string
{
    use TemRotulo;

    case Salario = 'salario';
    case Ferias = 'ferias';
    case DecimoTerceiro = 'decimo_terceiro';
    case Rescisao = 'rescisao';
    case Fornecedor = 'fornecedor';
    case Servico = 'servico';
    case Reembolso = 'reembolso';
    case Outro = 'outro';

    public function rotulo(): string
    {
        return match ($this) {
            self::Salario => 'Salário',
            self::Ferias => 'Férias',
            self::DecimoTerceiro => 'Décimo terceiro',
            self::Rescisao => 'Rescisão',
            self::Fornecedor => 'Fornecedor',
            self::Servico => 'Serviço',
            self::Reembolso => 'Reembolso',
            self::Outro => 'Outro',
        };
    }

    /**
     * Tipos que compoem a folha de pagamento nos relatorios.
     */
    public function ehFolha(): bool
    {
        return in_array($this, [self::Salario, self::Ferias, self::DecimoTerceiro, self::Rescisao], true);
    }
}
