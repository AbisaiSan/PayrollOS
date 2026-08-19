<?php

use App\Enums\TipoChavePix;

it('aceita chave do tipo e-mail bem formada', function () {
    expect(TipoChavePix::Email->formatoValido('financeiro@corebanx.com.br'))->toBeTrue();
});

it('rejeita e-mail malformado', function () {
    expect(TipoChavePix::Email->formatoValido('financeiro@'))->toBeFalse();
});

it('exige o padrão +55 no telefone', function () {
    expect(TipoChavePix::Telefone->formatoValido('+5511999998888'))->toBeTrue()
        ->and(TipoChavePix::Telefone->formatoValido('11999998888'))->toBeFalse();
});

it('aceita chave aleatória no formato UUID v4', function () {
    expect(TipoChavePix::Aleatoria->formatoValido('3f4a2b1c-9d8e-4f7a-b6c5-1a2b3c4d5e6f'))->toBeTrue()
        ->and(TipoChavePix::Aleatoria->formatoValido('nao-e-um-uuid'))->toBeFalse();
});

it('valida CPF e CNPJ como chave pela quantidade de dígitos', function () {
    expect(TipoChavePix::Cpf->formatoValido('529.982.247-25'))->toBeTrue()
        ->and(TipoChavePix::Cnpj->formatoValido('11.222.333/0001-81'))->toBeTrue()
        ->and(TipoChavePix::Cpf->formatoValido('1234'))->toBeFalse();
});
