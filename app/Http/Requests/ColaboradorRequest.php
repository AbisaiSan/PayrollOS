<?php

namespace App\Http\Requests;

use App\Enums\StatusColaborador;
use App\Enums\TipoContratacao;
use App\Rules\Cpf;
use App\Support\Documento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ColaboradorRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autorizacao fica na policy, aplicada no controller.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $colaboradorId = $this->route('colaborador')?->id;

        return [
            'nome' => ['required', 'string', 'max:255'],
            'cpf' => [
                'required',
                'string',
                new Cpf,
                Rule::unique('colaboradores', 'cpf')->ignore($colaboradorId)->whereNull('deleted_at'),
            ],
            'cargo' => ['required', 'string', 'max:255'],
            'departamento' => ['required', 'string', 'max:255'],
            'tipo_contrato' => ['required', new Enum(TipoContratacao::class)],
            'data_admissao' => ['required', 'date'],
            'data_desligamento' => ['nullable', 'date', 'after_or_equal:data_admissao'],
            'salario_base' => ['required', 'numeric', 'min:0', 'max:99999999999.99'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', new Enum(StatusColaborador::class)],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // CPF entra formatado do frontend e e gravado so com digitos.
        if ($this->has('cpf')) {
            $this->merge(['cpf' => Documento::apenasDigitos($this->input('cpf'))]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nome' => 'nome',
            'cpf' => 'CPF',
            'tipo_contrato' => 'tipo de contrato',
            'data_admissao' => 'data de admissão',
            'data_desligamento' => 'data de desligamento',
            'salario_base' => 'salário base',
        ];
    }
}
