<?php

namespace App\Console\Commands;

use App\Services\PagamentoService;
use Illuminate\Console\Command;

class MarcarPagamentosAtrasados extends Command
{
    protected $signature = 'payrollos:marcar-atrasados';

    protected $description = 'Move para Atrasado os pagamentos em aberto cujo vencimento já passou (regra 3.6)';

    public function handle(PagamentoService $service): int
    {
        $total = $service->marcarAtrasados();

        $this->info("Pagamentos marcados como atrasados: {$total}");

        return self::SUCCESS;
    }
}
