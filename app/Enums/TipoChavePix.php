<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

enum TipoChavePix: string
{
    use TemRotulo;

    case Cpf = 'cpf';
    case Cnpj = 'cnpj';
    case Email = 'email';
    case Telefone = 'telefone';
    case Aleatoria = 'aleatoria';

    public function rotulo(): string
    {
        return match ($this) {
            self::Cpf => 'CPF',
            self::Cnpj => 'CNPJ',
            self::Email => 'E-mail',
            self::Telefone => 'Telefone',
            self::Aleatoria => 'Chave aleatória',
        };
    }

    /**
     * Valida o formato da chave conforme o tipo. Nao consulta o DICT, apenas formato
     * (integracao com API do Pix esta fora de escopo, ver secao 7 do plano).
     */
    public function formatoValido(string $chave): bool
    {
        $chave = trim($chave);

        return match ($this) {
            self::Cpf => strlen(preg_replace('/\D/', '', $chave)) === 11,
            self::Cnpj => strlen(preg_replace('/\D/', '', $chave)) === 14,
            self::Email => filter_var($chave, FILTER_VALIDATE_EMAIL) !== false,
            // Telefone no padrao Pix: +55 seguido de DDD e numero (10 ou 11 digitos).
            self::Telefone => (bool) preg_match('/^\+55\d{10,11}$/', preg_replace('/[\s()-]/', '', $chave)),
            // EVP: UUID v4.
            self::Aleatoria => (bool) preg_match(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                $chave
            ),
        };
    }
}
