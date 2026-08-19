<?php

namespace App\Http\Requests;

use App\Enums\Periodicidade;
use App\Enums\StatusContrato;
use App\Enums\TipoContrato;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ContratoRequest extends FormRequest
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
        $ehRecorrente = $this->input('tipo') === TipoContrato::Recorrente->value;

        return [
            'fornecedor_id' => ['required', 'exists:fornecedores,id'],
            'categoria_id' => ['nullable', 'exists:categorias_pagamento,id'],
            'conta_bancaria_id' => ['nullable', 'exists:contas_bancarias,id'],
            'descricao' => ['required', 'string', 'max:255'],
            'tipo' => ['required', new Enum(TipoContrato::class)],
            'valor' => ['required', 'numeric', 'min:0.01', 'max:99999999999.99'],
            // Sem periodicidade e dia de vencimento a rotina de geracao nao tem
            // como calcular a proxima competencia (regra 3.6).
            'periodicidade' => [Rule::requiredIf($ehRecorrente), 'nullable', new Enum(Periodicidade::class)],
            'dia_vencimento' => [Rule::requiredIf($ehRecorrente), 'nullable', 'integer', 'between:1,31'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'status' => ['required', new Enum(StatusContrato::class)],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'fornecedor_id' => 'fornecedor',
            'categoria_id' => 'categoria',
            'conta_bancaria_id' => 'conta de destino',
            'descricao' => 'descrição',
            'dia_vencimento' => 'dia de vencimento',
            'data_inicio' => 'data de início',
            'data_fim' => 'data de término',
        ];
    }
}
