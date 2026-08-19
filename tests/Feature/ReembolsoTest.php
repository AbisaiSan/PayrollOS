<?php

use App\Enums\StatusReembolso;
use App\Exceptions\RegraDeNegocioException;
use App\Models\Colaborador;
use App\Models\Reembolso;
use App\Services\ReembolsoService;

beforeEach(function () {
    $this->service = app(ReembolsoService::class);
});

it('nasce pendente e já registra o histórico', function () {
    $colaborador = Colaborador::factory()->create();

    $reembolso = $this->service->solicitar($colaborador, [
        'descricao' => 'Uber para reunião',
        'categoria' => 'transporte',
        'valor' => 48.90,
    ]);

    expect($reembolso->status)->toBe(StatusReembolso::Pendente)
        ->and($reembolso->historicoStatus)->toHaveCount(1);
});

it('percorre o fluxo pendente, aprovado e pago', function () {
    $reembolso = Reembolso::factory()->create();

    $this->service->aprovar($reembolso);
    expect($reembolso->fresh()->status)->toBe(StatusReembolso::Aprovado);

    $this->service->confirmarPagamento($reembolso->fresh());
    expect($reembolso->fresh()->status)->toBe(StatusReembolso::Pago)
        ->and($reembolso->fresh()->data_pagamento)->not->toBeNull();
});

it('não pula de pendente direto para pago', function () {
    $reembolso = Reembolso::factory()->create();

    expect(fn () => $this->service->confirmarPagamento($reembolso))
        ->toThrow(RegraDeNegocioException::class);
});

it('não permite editar um reembolso já pago', function () {
    $reembolso = Reembolso::factory()->comStatus(StatusReembolso::Pago)->create();

    expect(fn () => $this->service->atualizar($reembolso, ['valor' => 999]))
        ->toThrow(RegraDeNegocioException::class);
});

it('registra o motivo ao rejeitar', function () {
    $reembolso = Reembolso::factory()->create();

    $this->service->rejeitar($reembolso, 'Sem comprovante fiscal.');

    expect($reembolso->fresh()->status)->toBe(StatusReembolso::Rejeitado)
        ->and($reembolso->fresh()->historicoStatus->first()->observacao)
        ->toBe('Sem comprovante fiscal.');
});
