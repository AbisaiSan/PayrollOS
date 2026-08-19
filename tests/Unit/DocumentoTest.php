<?php

use App\Support\Documento;

it('valida CPF correto', function () {
    expect(Documento::cpfValido('529.982.247-25'))->toBeTrue();
});

it('rejeita CPF com dígito verificador errado', function () {
    expect(Documento::cpfValido('529.982.247-26'))->toBeFalse();
});

it('rejeita CPF com todos os dígitos iguais', function () {
    expect(Documento::cpfValido('111.111.111-11'))->toBeFalse();
});

it('valida CNPJ correto', function () {
    expect(Documento::cnpjValido('11.222.333/0001-81'))->toBeTrue();
});

it('rejeita CNPJ com dígito verificador errado', function () {
    expect(Documento::cnpjValido('11.222.333/0001-82'))->toBeFalse();
});

it('formata CPF e CNPJ para exibição', function () {
    expect(Documento::formatar('52998224725'))->toBe('529.982.247-25')
        ->and(Documento::formatar('11222333000181'))->toBe('11.222.333/0001-81');
});
