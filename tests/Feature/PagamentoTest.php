<?php

use App\Enums\FormaPagamento;
use App\Enums\StatusPagamento;
use App\Exceptions\RegraDeNegocioException;
use App\Models\CategoriaPagamento;
use App\Models\Colaborador;
use App\Models\ContaBancaria;
use App\Models\Fornecedor;
use App\Models\Pagamento;
use App\Services\PagamentoService;

beforeEach(function () {
    $this->service = app(PagamentoService::class);
    $this->categoria = CategoriaPagamento::factory()->create();
});

it('registra o lançamento inicial no histórico de status', function () {
    $colaborador = Colaborador::factory()->create();

    $pagamento = $this->service->lancar($colaborador, dadosDePagamento($this->categoria->id));

    expect($pagamento->status)->toBe(StatusPagamento::Pendente)
        ->and($pagamento->historicoStatus)->toHaveCount(1)
        ->and($pagamento->historicoStatus->first()->status_novo)->toBe('pendente');
});

it('bloqueia lançamento para colaborador desligado', function () {
    $colaborador = Colaborador::factory()->desligado()->create();

    expect(fn () => $this->service->lancar($colaborador, dadosDePagamento($this->categoria->id)))
        ->toThrow(RegraDeNegocioException::class);
});

it('bloqueia lançamento para fornecedor inativo', function () {
    $fornecedor = Fornecedor::factory()->inativo()->create();

    expect(fn () => $this->service->lancar($fornecedor, dadosDePagamento($this->categoria->id)))
        ->toThrow(RegraDeNegocioException::class);
});

it('recusa conta de destino que pertence a outro beneficiário', function () {
    $colaborador = Colaborador::factory()->create();
    $outro = Colaborador::factory()->create();

    $contaDeOutro = ContaBancaria::factory()->create([
        'owner_type' => 'colaborador',
        'owner_id' => $outro->id,
    ]);

    expect(fn () => $this->service->lancar(
        $colaborador,
        dadosDePagamento($this->categoria->id, ['conta_bancaria_id' => $contaDeOutro->id])
    ))->toThrow(RegraDeNegocioException::class);
});

it('preenche a data de pagamento ao confirmar', function () {
    $pagamento = Pagamento::factory()->create(['categoria_id' => $this->categoria->id]);

    $this->service->confirmarPagamento($pagamento, now()->subDay());

    expect($pagamento->fresh()->status)->toBe(StatusPagamento::Pago)
        ->and($pagamento->fresh()->data_pagamento->toDateString())
        ->toBe(now()->subDay()->toDateString());
});

it('limpa a data de pagamento ao reverter um pagamento confirmado', function () {
    $pagamento = Pagamento::factory()->comStatus(StatusPagamento::Pago)->create([
        'categoria_id' => $this->categoria->id,
    ]);

    $this->service->alterarStatus($pagamento, StatusPagamento::Pendente, 'Confirmado por engano.');

    expect($pagamento->fresh()->data_pagamento)->toBeNull();
});

it('recusa transição de status inválida', function () {
    $pagamento = Pagamento::factory()->comStatus(StatusPagamento::Cancelado)->create([
        'categoria_id' => $this->categoria->id,
    ]);

    expect(fn () => $this->service->alterarStatus($pagamento, StatusPagamento::Pago))
        ->toThrow(RegraDeNegocioException::class);
});

it('grava cada mudança de status no histórico', function () {
    $pagamento = Pagamento::factory()->create(['categoria_id' => $this->categoria->id]);

    $this->service->alterarStatus($pagamento, StatusPagamento::Agendado);
    $this->service->confirmarPagamento($pagamento->fresh());

    expect($pagamento->fresh()->historicoStatus)->toHaveCount(2);
});

it('marca como atrasado apenas o que venceu e continua em aberto', function () {
    $vencido = Pagamento::factory()->vencido()->create(['categoria_id' => $this->categoria->id]);
    $noPrazo = Pagamento::factory()->create(['categoria_id' => $this->categoria->id]);
    $pago = Pagamento::factory()->vencido()->comStatus(StatusPagamento::Pago)->create([
        'categoria_id' => $this->categoria->id,
    ]);

    $total = $this->service->marcarAtrasados();

    expect($total)->toBe(1)
        ->and($vencido->fresh()->status)->toBe(StatusPagamento::Atrasado)
        ->and($noPrazo->fresh()->status)->toBe(StatusPagamento::Pendente)
        ->and($pago->fresh()->status)->toBe(StatusPagamento::Pago);
});

/**
 * @param  array<string, mixed>  $sobrescritas
 * @return array<string, mixed>
 */
function dadosDePagamento(int $categoriaId, array $sobrescritas = []): array
{
    return array_merge([
        'categoria_id' => $categoriaId,
        'descricao' => 'Salário de agosto',
        'valor' => 5000.00,
        'data_vencimento' => now()->addDays(5)->toDateString(),
        'forma_pagamento' => FormaPagamento::Pix->value,
    ], $sobrescritas);
}
