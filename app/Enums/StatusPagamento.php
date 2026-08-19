<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

/**
 * Ciclo de vida do pagamento (regra 3.6).
 *
 * O sistema nao executa o pagamento: "Pago" e sempre uma confirmacao manual do
 * usuario. "Atrasado" e derivado pela rotina diaria, nao escolhido no formulario.
 *
 * Quando o fluxo de aprovacao entrar (secao 7 do plano), basta acrescentar os
 * casos novos e as transicoes correspondentes em transicoesPermitidas().
 */
enum StatusPagamento: string
{
    use TemRotulo;

    case Pendente = 'pendente';
    case Agendado = 'agendado';
    case Pago = 'pago';
    case Atrasado = 'atrasado';
    case Cancelado = 'cancelado';

    public function rotulo(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Agendado => 'Agendado',
            self::Pago => 'Pago',
            self::Atrasado => 'Atrasado',
            self::Cancelado => 'Cancelado',
        };
    }

    /**
     * Cor do badge no frontend (severity do PrimeVue).
     */
    public function severidade(): string
    {
        return match ($this) {
            self::Pendente => 'warn',
            self::Agendado => 'info',
            self::Pago => 'success',
            self::Atrasado => 'danger',
            self::Cancelado => 'secondary',
        };
    }

    /**
     * Status para os quais este pode mudar.
     *
     * @return array<int, self>
     */
    public function transicoesPermitidas(): array
    {
        return match ($this) {
            self::Pendente => [self::Agendado, self::Pago, self::Atrasado, self::Cancelado],
            self::Agendado => [self::Pendente, self::Pago, self::Atrasado, self::Cancelado],
            self::Atrasado => [self::Agendado, self::Pago, self::Cancelado],
            // Reverter um pagamento confirmado por engano: permitido, mas fica no historico.
            self::Pago => [self::Cancelado, self::Pendente],
            self::Cancelado => [],
        };
    }

    public function podeIrPara(self $destino): bool
    {
        return in_array($destino, $this->transicoesPermitidas(), true);
    }

    /**
     * Status que ainda contam como valor a pagar nos indicadores do dashboard.
     */
    public function estaEmAberto(): bool
    {
        return in_array($this, [self::Pendente, self::Agendado, self::Atrasado], true);
    }

    /**
     * Statuses em aberto, para uso em consultas.
     *
     * @return array<int, string>
     */
    public static function valoresEmAberto(): array
    {
        return array_column(
            array_filter(self::cases(), fn (self $caso) => $caso->estaEmAberto()),
            'value'
        );
    }
}
