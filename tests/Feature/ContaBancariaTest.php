<?php

use App\Enums\StatusContaBancaria;
use App\Exceptions\RegraDeNegocioException;
use App\Models\Colaborador;
use App\Models\ContaBancaria;
use App\Services\ContaBancariaService;

beforeEach(function () {
    $this->service = app(ContaBancariaService::class);
});

it('marca a primeira conta do beneficiário como principal automaticamente', function () {
    $colaborador = Colaborador::factory()->create();

    $conta = $this->service->criar($colaborador, dadosDeConta(['principal' => false]));

    expect($conta->principal)->toBeTrue();
});

it('mantém no máximo uma conta principal por beneficiário', function () {
    $colaborador = Colaborador::factory()->create();

    $primeira = $this->service->criar($colaborador, dadosDeConta());
    $segunda = $this->service->criar($colaborador, dadosDeConta(['principal' => true]));

    expect($segunda->fresh()->principal)->toBeTrue()
        ->and($primeira->fresh()->principal)->toBeFalse()
        ->and($colaborador->contasBancarias()->where('principal', true)->count())->toBe(1);
});

it('troca a conta principal sem deixar o beneficiário sem destino padrão', function () {
    $colaborador = Colaborador::factory()->create();

    $primeira = $this->service->criar($colaborador, dadosDeConta());
    $segunda = $this->service->criar($colaborador, dadosDeConta());

    $this->service->definirPrincipal($segunda);

    expect($colaborador->contasBancarias()->where('principal', true)->count())->toBe(1)
        ->and($segunda->fresh()->principal)->toBeTrue()
        ->and($primeira->fresh()->principal)->toBeFalse();
});

it('não permite marcar uma conta inativa como principal', function () {
    $colaborador = Colaborador::factory()->create();
    $conta = ContaBancaria::factory()->inativa()->create([
        'owner_type' => 'colaborador',
        'owner_id' => $colaborador->id,
    ]);

    expect(fn () => $this->service->definirPrincipal($conta))
        ->toThrow(RegraDeNegocioException::class);
});

it('inativa a conta em vez de excluir, preservando o histórico', function () {
    $colaborador = Colaborador::factory()->create();
    $conta = $this->service->criar($colaborador, dadosDeConta());

    $this->service->inativar($conta);

    expect($conta->fresh()->status)->toBe(StatusContaBancaria::Inativa)
        ->and(ContaBancaria::find($conta->id))->not->toBeNull();
});

it('exige outra conta principal antes de inativar a atual', function () {
    $colaborador = Colaborador::factory()->create();

    $principal = $this->service->criar($colaborador, dadosDeConta());
    $this->service->criar($colaborador, dadosDeConta());

    expect(fn () => $this->service->inativar($principal))
        ->toThrow(RegraDeNegocioException::class);
});

/**
 * @param  array<string, mixed>  $sobrescritas
 * @return array<string, mixed>
 */
function dadosDeConta(array $sobrescritas = []): array
{
    return array_merge([
        'banco' => 'Itaú',
        'agencia' => '1234',
        'conta' => '56789',
        'digito' => '0',
        'tipo_conta' => 'corrente',
        'titular_nome' => 'Fulano de Tal',
        'titular_documento' => '52998224725',
        'principal' => false,
    ], $sobrescritas);
}
