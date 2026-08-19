<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;
use Carbon\CarbonInterface;

enum Periodicidade: string
{
    use TemRotulo;

    case Mensal = 'mensal';
    case Quinzenal = 'quinzenal';
    case Anual = 'anual';

    public function rotulo(): string
    {
        return match ($this) {
            self::Mensal => 'Mensal',
            self::Quinzenal => 'Quinzenal',
            self::Anual => 'Anual',
        };
    }

    /**
     * Avanca uma competencia a partir de uma data de vencimento.
     */
    public function proximoVencimento(CarbonInterface $vencimentoAtual): CarbonInterface
    {
        return match ($this) {
            self::Mensal => $vencimentoAtual->copy()->addMonthNoOverflow(),
            self::Quinzenal => $vencimentoAtual->copy()->addDays(15),
            self::Anual => $vencimentoAtual->copy()->addYearNoOverflow(),
        };
    }
}
