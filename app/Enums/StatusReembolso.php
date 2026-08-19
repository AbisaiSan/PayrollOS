<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

/**
 * Regra 3.7: os estados de aprovacao ja existem, mas hoje nenhum usuario e
 * obrigado a aprovar antes de pagar. Quando a regra formal entrar, ela restringe
 * quem chama a transicao, sem alterar este enum.
 */
enum StatusReembolso: string
{
    use TemRotulo;

    case Pendente = 'pendente';
    case Aprovado = 'aprovado';
    case Pago = 'pago';
    case Rejeitado = 'rejeitado';

    public function rotulo(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Aprovado => 'Aprovado',
            self::Pago => 'Pago',
            self::Rejeitado => 'Rejeitado',
        };
    }

    public function severidade(): string
    {
        return match ($this) {
            self::Pendente => 'warn',
            self::Aprovado => 'info',
            self::Pago => 'success',
            self::Rejeitado => 'danger',
        };
    }

    /**
     * @return array<int, self>
     */
    public function transicoesPermitidas(): array
    {
        return match ($this) {
            self::Pendente => [self::Aprovado, self::Rejeitado],
            self::Aprovado => [self::Pago, self::Rejeitado, self::Pendente],
            self::Pago => [self::Aprovado],
            self::Rejeitado => [self::Pendente],
        };
    }

    public function podeIrPara(self $destino): bool
    {
        return in_array($destino, $this->transicoesPermitidas(), true);
    }

    public function estaEmAberto(): bool
    {
        return in_array($this, [self::Pendente, self::Aprovado], true);
    }
}
