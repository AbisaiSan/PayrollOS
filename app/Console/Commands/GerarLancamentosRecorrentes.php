<?php

namespace App\Console\Commands;

use App\Services\ContratoRecorrenteService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GerarLancamentosRecorrentes extends Command
{
    protected $signature = 'payrollos:gerar-lancamentos
                            {--dias= : Dias de antecedência (padrão: config payrollos.antecedencia_geracao_dias)}
                            {--dry-run : Executa e desfaz, apenas para conferir o que seria gerado}';

    protected $description = 'Gera os pagamentos pendentes dos contratos recorrentes (regra 3.6)';

    public function handle(ContratoRecorrenteService $service): int
    {
        $dias = $this->option('dias') !== null ? (int) $this->option('dias') : null;

        if ($this->option('dry-run')) {
            $this->warn('Modo dry-run: as alterações serão desfeitas ao final.');

            // Reaproveita a rotina real e desfaz, em vez de manter uma logica de
            // simulacao paralela que sairia do sincronismo com o tempo.
            DB::beginTransaction();

            try {
                $this->relatar($service->gerarLancamentosPendentes($dias));
            } finally {
                DB::rollBack();
            }

            return self::SUCCESS;
        }

        $this->relatar($service->gerarLancamentosPendentes($dias));

        return self::SUCCESS;
    }

    /**
     * @param  array{gerados: int, contratos: int, ignorados: array<int, string>}  $resultado
     */
    private function relatar(array $resultado): void
    {
        $this->info("Contratos avaliados: {$resultado['contratos']}");
        $this->info("Lançamentos gerados: {$resultado['gerados']}");

        foreach ($resultado['ignorados'] as $contratoId => $motivo) {
            $this->warn("Contrato #{$contratoId} ignorado: {$motivo}");
        }
    }
}
