<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Rotinas agendadas do PayrollOS
|--------------------------------------------------------------------------
|
| Rodam via `php artisan schedule:run` a cada minuto no cron do servidor.
|
*/

// Cria os lancamentos dos contratos recorrentes alguns dias antes do vencimento (regra 3.6).
Schedule::command('payrollos:gerar-lancamentos')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->onOneServer();

// Promove para Atrasado o que venceu sem confirmacao. Roda depois da geracao para
// nao marcar como atrasado algo criado no mesmo ciclo.
Schedule::command('payrollos:marcar-atrasados')
    ->dailyAt('06:15')
    ->withoutOverlapping()
    ->onOneServer();
