<?php

use App\Enums\Periodicidade;
use App\Enums\StatusContrato;
use App\Enums\StatusPagamento;
use App\Enums\TipoCategoria;
use App\Models\CategoriaPagamento;
use App\Models\Contrato;
use App\Models\Fornecedor;
use App\Models\Pagamento;
use App\Services\ContratoRecorrenteService;

beforeEach(function () {
    $this->service = app(ContratoRecorrenteService::class);
    $this->categoria = CategoriaPagamento::factory()->doTipo(TipoCategoria::Servico)->create();
});

it('gera lançamento pendente para contrato recorrente ativo', function () {
    $contrato = Contrato::factory()->create([
        'categoria_id' => $this->categoria->id,
        'dia_vencimento' => now()->addDays(2)->day,
        'data_inicio' => now()->startOfMonth(),
        'proximo_vencimento' => now()->addDays(2)->toDateString(),
    ]);

    $resultado = $this->service->gerarLancamentosPendentes(5);

    expect($resultado['gerados'])->toBe(1);

    $pagamento = Pagamento::where('contrato_id', $contrato->id)->first();

    expect($pagamento)->not->toBeNull()
        ->and($pagamento->status)->toBe(StatusPagamento::Pendente)
        ->and((float) $pagamento->valor)->toBe((float) $contrato->valor);
});

it('não gera nada para contrato encerrado ou suspenso', function (StatusContrato $status) {
    Contrato::factory()->create([
        'categoria_id' => $this->categoria->id,
        'status' => $status,
        'proximo_vencimento' => now()->addDay()->toDateString(),
    ]);

    expect($this->service->gerarLancamentosPendentes(5)['gerados'])->toBe(0);
})->with([StatusContrato::Encerrado, StatusContrato::Suspenso]);

it('não gera nada para contrato pontual', function () {
    Contrato::factory()->pontual()->create([
        'categoria_id' => $this->categoria->id,
        'proximo_vencimento' => now()->addDay()->toDateString(),
    ]);

    expect($this->service->gerarLancamentosPendentes(5)['gerados'])->toBe(0);
});

it('é idempotente: rodar duas vezes não duplica o lançamento', function () {
    $contrato = Contrato::factory()->create([
        'categoria_id' => $this->categoria->id,
        'proximo_vencimento' => now()->addDays(2)->toDateString(),
    ]);

    $this->service->gerarLancamentosPendentes(5);
    $this->service->gerarLancamentosPendentes(5);

    expect(Pagamento::where('contrato_id', $contrato->id)->count())->toBe(1);
});

it('gera os vencimentos acumulados quando a rotina fica dias sem rodar', function () {
    $contrato = Contrato::factory()->create([
        'categoria_id' => $this->categoria->id,
        'periodicidade' => Periodicidade::Quinzenal,
        'data_inicio' => now()->subMonths(2),
        'proximo_vencimento' => now()->subDays(40)->toDateString(),
    ]);

    $this->service->gerarLancamentosPendentes(5);

    // 40 dias parados, a cada 15: tres vencimentos ficaram para tras.
    expect(Pagamento::where('contrato_id', $contrato->id)->count())->toBeGreaterThan(1);
});

it('ignora contrato cujo fornecedor está inativo', function () {
    $fornecedor = Fornecedor::factory()->inativo()->create();

    $contrato = Contrato::factory()->create([
        'fornecedor_id' => $fornecedor->id,
        'categoria_id' => $this->categoria->id,
        'proximo_vencimento' => now()->addDay()->toDateString(),
    ]);

    $resultado = $this->service->gerarLancamentosPendentes(5);

    expect($resultado['gerados'])->toBe(0)
        ->and($resultado['ignorados'])->toHaveKey($contrato->id);
});

it('para de gerar depois da data de término do contrato', function () {
    $contrato = Contrato::factory()->create([
        'categoria_id' => $this->categoria->id,
        'data_inicio' => now()->subMonths(3),
        'data_fim' => now()->subDay()->toDateString(),
        'proximo_vencimento' => now()->addDay()->toDateString(),
    ]);

    expect($this->service->gerarLancamentosPendentes(5)['gerados'])->toBe(0)
        ->and(Pagamento::where('contrato_id', $contrato->id)->count())->toBe(0);
});
