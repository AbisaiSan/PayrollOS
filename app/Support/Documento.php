<?php

namespace App\Support;

/**
 * Validacao e formatacao de CPF/CNPJ.
 *
 * Documentos sao sempre gravados so com digitos; a formatacao acontece na exibicao.
 */
final class Documento
{
    public static function apenasDigitos(?string $valor): string
    {
        return preg_replace('/\D/', '', (string) $valor);
    }

    public static function cpfValido(?string $cpf): bool
    {
        $cpf = self::apenasDigitos($cpf);

        if (strlen($cpf) !== 11) {
            return false;
        }

        // Sequencias repetidas (000..., 111...) passam no calculo do digito, mas nao existem.
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($posicao = 9; $posicao < 11; $posicao++) {
            $soma = 0;

            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cpf[$i] * (($posicao + 1) - $i);
            }

            $digito = ((10 * $soma) % 11) % 10;

            if ((int) $cpf[$posicao] !== $digito) {
                return false;
            }
        }

        return true;
    }

    public static function cnpjValido(?string $cnpj): bool
    {
        $cnpj = self::apenasDigitos($cnpj);

        if (strlen($cnpj) !== 14) {
            return false;
        }

        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $calcularDigito = function (string $base): int {
            $peso = strlen($base) - 7;
            $soma = 0;

            foreach (str_split($base) as $i => $numero) {
                $soma += (int) $numero * $peso;
                $peso = $peso === 2 ? 9 : $peso - 1;
            }

            $resto = $soma % 11;

            return $resto < 2 ? 0 : 11 - $resto;
        };

        $primeiro = $calcularDigito(substr($cnpj, 0, 12));

        if ((int) $cnpj[12] !== $primeiro) {
            return false;
        }

        return (int) $cnpj[13] === $calcularDigito(substr($cnpj, 0, 13));
    }

    public static function valido(?string $documento): bool
    {
        $digitos = self::apenasDigitos($documento);

        return match (strlen($digitos)) {
            11 => self::cpfValido($digitos),
            14 => self::cnpjValido($digitos),
            default => false,
        };
    }

    /**
     * Formata para exibicao: 000.000.000-00 ou 00.000.000/0000-00.
     */
    public static function formatar(?string $documento): string
    {
        $digitos = self::apenasDigitos($documento);

        return match (strlen($digitos)) {
            11 => preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digitos),
            14 => preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $digitos),
            default => $digitos,
        };
    }
}
