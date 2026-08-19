<?php

namespace App\Enums\Concerns;

/**
 * Expoe rotulos legiveis e listas prontas para popular selects no frontend.
 *
 * O enum que usa este trait deve implementar rotulo(): string.
 */
trait TemRotulo
{
    /**
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Lista no formato consumido pelos selects do PrimeVue.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function opcoes(): array
    {
        return array_map(
            fn (self $caso) => ['value' => $caso->value, 'label' => $caso->rotulo()],
            self::cases()
        );
    }
}
