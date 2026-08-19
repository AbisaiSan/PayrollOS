<?php

namespace App\Http\Requests;

use App\Enums\TipoCategoria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CategoriaPagamentoRequest extends FormRequest
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
        return [
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categorias_pagamento', 'nome')->ignore($this->route('categoria')?->id),
            ],
            'tipo' => ['required', new Enum(TipoCategoria::class)],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['ativo' => $this->boolean('ativo')]);
    }
}
