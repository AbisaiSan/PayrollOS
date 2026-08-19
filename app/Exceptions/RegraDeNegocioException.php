<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Violacao de regra de negocio do dominio.
 *
 * Chega ao usuario como erro de validacao no formulario, nao como erro 500.
 */
class RegraDeNegocioException extends RuntimeException
{
    public function __construct(string $mensagem, private readonly string $campo = 'geral')
    {
        parent::__construct($mensagem);
    }

    public function render(): never
    {
        throw ValidationException::withMessages([
            $this->campo => $this->getMessage(),
        ]);
    }
}
