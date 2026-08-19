<?php

namespace App\Http\Requests;

use App\Enums\StatusFornecedor;
use App\Enums\TipoFornecedor;
use App\Enums\TipoPessoa;
use App\Rules\CpfOuCnpj;
use App\Support\Documento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class FornecedorRequest extends FormRequest
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
        $fornecedorId = $this->route('fornecedor')?->id;
        $tipoPessoa = TipoPessoa::tryFrom((string) $this->input('tipo_pessoa'));

        return [
            'tipo_pessoa' => ['required', new Enum(TipoPessoa::class)],
            'razao_social' => ['required', 'string', 'max:255'],
            'nome_fantasia' => ['nullable', 'string', 'max:255'],
            'documento' => [
                'required',
                'string',
                new CpfOuCnpj($tipoPessoa),
                Rule::unique('fornecedores', 'documento')->ignore($fornecedorId)->whereNull('deleted_at'),
            ],
            'tipo_fornecedor' => ['required', new Enum(TipoFornecedor::class)],
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'status' => ['required', new Enum(StatusFornecedor::class)],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('documento')) {
            $this->merge(['documento' => Documento::apenasDigitos($this->input('documento'))]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tipo_pessoa' => 'tipo de pessoa',
            'razao_social' => 'razão social / nome',
            'nome_fantasia' => 'nome fantasia',
            'documento' => 'CPF/CNPJ',
            'tipo_fornecedor' => 'tipo de fornecedor',
        ];
    }
}
