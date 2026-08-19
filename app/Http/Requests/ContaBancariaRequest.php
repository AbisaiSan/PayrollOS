<?php

namespace App\Http\Requests;

use App\Enums\StatusContaBancaria;
use App\Enums\TipoChavePix;
use App\Enums\TipoConta;
use App\Rules\ChavePix;
use App\Support\Documento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ContaBancariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tipoChave = TipoChavePix::tryFrom((string) $this->input('tipo_chave_pix'));

        return [
            'banco' => ['required', 'string', 'max:255'],
            'codigo_banco' => ['nullable', 'string', 'max:5'],
            'agencia' => ['required', 'string', 'max:10'],
            'conta' => ['required', 'string', 'max:20'],
            'digito' => ['nullable', 'string', 'max:2'],
            'tipo_conta' => ['required', new Enum(TipoConta::class)],
            'titular_nome' => ['required', 'string', 'max:255'],
            'titular_documento' => ['required', 'string', function ($attribute, $value, $fail) {
                if (! Documento::valido($value)) {
                    $fail('O documento do titular deve ser um CPF ou CNPJ válido.');
                }
            }],
            // Chave e tipo andam juntos: um sem o outro nao da para validar formato.
            'tipo_chave_pix' => ['nullable', 'required_with:chave_pix', new Enum(TipoChavePix::class)],
            'chave_pix' => ['nullable', 'required_with:tipo_chave_pix', 'string', 'max:255', new ChavePix($tipoChave)],
            'principal' => ['boolean'],
            'status' => ['nullable', new Enum(StatusContaBancaria::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('titular_documento')) {
            $this->merge([
                'titular_documento' => Documento::apenasDigitos($this->input('titular_documento')),
            ]);
        }

        $this->merge(['principal' => $this->boolean('principal')]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tipo_conta' => 'tipo de conta',
            'titular_nome' => 'nome do titular',
            'titular_documento' => 'documento do titular',
            'chave_pix' => 'chave Pix',
            'tipo_chave_pix' => 'tipo da chave Pix',
        ];
    }
}
