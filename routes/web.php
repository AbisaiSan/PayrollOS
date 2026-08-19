<?php

use App\Http\Controllers\AnexoController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\CategoriaPagamentoController;
use App\Http\Controllers\ColaboradorController;
use App\Http\Controllers\ContaBancariaController;
use App\Http\Controllers\ContratoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\PagamentoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReembolsoController;
use App\Http\Controllers\RelatorioController;
use Illuminate\Support\Facades\Route;

// Sistema interno: nao ha area publica, a raiz vai direto para o login.
Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Colaboradores e contas bancarias (Fase 1)
    |--------------------------------------------------------------------------
    */
    Route::resource('colaboradores', ColaboradorController::class)
        ->parameters(['colaboradores' => 'colaborador']);

    Route::post('colaboradores/{colaborador}/desligar', [ColaboradorController::class, 'desligar'])
        ->name('colaboradores.desligar');

    /*
    |--------------------------------------------------------------------------
    | Fornecedores e contratos (Fase 2)
    |--------------------------------------------------------------------------
    */
    Route::resource('fornecedores', FornecedorController::class)
        ->parameters(['fornecedores' => 'fornecedor']);

    Route::resource('contratos', ContratoController::class);

    /*
    |--------------------------------------------------------------------------
    | Contas bancarias / chaves Pix
    |--------------------------------------------------------------------------
    |
    | Aninhadas no beneficiario: o tipo vem na URL porque a tabela e polimorfica
    | e compartilhada entre colaborador e fornecedor (regra 3.2).
    |
    */
    Route::prefix('{tipoBeneficiario}/{beneficiarioId}/contas')
        ->whereIn('tipoBeneficiario', ['colaborador', 'fornecedor'])
        ->name('contas.')
        ->group(function () {
            Route::post('/', [ContaBancariaController::class, 'store'])->name('store');
            Route::put('{conta}', [ContaBancariaController::class, 'update'])->name('update');
            Route::post('{conta}/principal', [ContaBancariaController::class, 'definirPrincipal'])->name('principal');
            Route::post('{conta}/inativar', [ContaBancariaController::class, 'inativar'])->name('inativar');
            Route::post('{conta}/reativar', [ContaBancariaController::class, 'reativar'])->name('reativar');
        });

    /*
    |--------------------------------------------------------------------------
    | Categorias e pagamentos (Fase 3, modulo central)
    |--------------------------------------------------------------------------
    */
    Route::resource('categorias', CategoriaPagamentoController::class)
        ->parameters(['categorias' => 'categoria'])
        ->except(['show']);

    Route::resource('pagamentos', PagamentoController::class);

    Route::post('pagamentos/{pagamento}/status', [PagamentoController::class, 'alterarStatus'])
        ->name('pagamentos.status');
    Route::post('pagamentos/{pagamento}/confirmar', [PagamentoController::class, 'confirmar'])
        ->name('pagamentos.confirmar');

    /*
    |--------------------------------------------------------------------------
    | Reembolsos (Fase 4)
    |--------------------------------------------------------------------------
    */
    Route::resource('reembolsos', ReembolsoController::class);

    Route::post('reembolsos/{reembolso}/status', [ReembolsoController::class, 'alterarStatus'])
        ->name('reembolsos.status');

    /*
    |--------------------------------------------------------------------------
    | Anexos e comprovantes (Fase 5)
    |--------------------------------------------------------------------------
    */
    Route::prefix('{tipoRegistro}/{registroId}/anexos')
        ->whereIn('tipoRegistro', ['pagamento', 'reembolso', 'contrato'])
        ->name('anexos.')
        ->group(function () {
            Route::post('/', [AnexoController::class, 'store'])->name('store');
        });

    // Download passa por rota autenticada: comprovante nao fica em disco publico.
    Route::get('anexos/{anexo}/download', [AnexoController::class, 'download'])->name('anexos.download');
    Route::delete('anexos/{anexo}', [AnexoController::class, 'destroy'])->name('anexos.destroy');

    Route::get('auditoria', AuditoriaController::class)->name('auditoria.index');

    /*
    |--------------------------------------------------------------------------
    | Relatorios (Fase 6)
    |--------------------------------------------------------------------------
    */
    Route::get('relatorios', [RelatorioController::class, 'index'])->name('relatorios.index');
    Route::get('relatorios/exportar', [RelatorioController::class, 'exportar'])->name('relatorios.exportar');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
