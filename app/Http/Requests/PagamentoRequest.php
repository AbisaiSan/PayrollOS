<?php

namespace App\Http\Requests;

use App\Enums\FormaPagamento;
use App\Enums\StatusPagamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class PagamentoRequest extends FormRequest
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
        $exigeConta = FormaPagamento::tryFrom((string) $this->input('forma_pagamento'))?->exigeContaDestino() ?? false;

        return [
            'payable_type' => ['required', Rule::in(['colaborador', 'fornecedor'])],
            'payable_id' => ['required', 'integer'],
            'categoria_id' => ['required', 'exists:categorias_pagamento,id'],
            'contrato_id' => ['nullable', 'exists:contratos,id'],
            // Pix e TED precisam de destino; boleto e dinheiro nao.
            'conta_bancaria_id' => [Rule::requiredIf($exigeConta), 'nullable', 'exists:contas_bancarias,id'],
            'competencia' => ['nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:0.01', 'max:99999999999.99'],
            'data_vencimento' => ['required', 'date'],
            'forma_pagamento' => ['required', new Enum(FormaPagamento::class)],
            'status' => ['nullable', Rule::in([
                StatusPagamento::Pendente->value,
                StatusPagamento::Agendado->value,
            ])],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'competencia.regex' => 'A competência deve estar no formato AAAA-MM (ex: 2026-08).',
            'status.in' => 'Um lançamento só pode nascer como Pendente ou Agendado.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'payable_type' => 'tipo de beneficiário',
            'payable_id' => 'beneficiário',
            'categoria_id' => 'categoria',
            'conta_bancaria_id' => 'conta de destino',
            'competencia' => 'competência',
            'descricao' => 'descrição',
            'data_vencimento' => 'data de vencimento',
            'forma_pagamento' => 'forma de pagamento',
        ];
    }
}
