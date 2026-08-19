<?php

namespace App\Http\Requests;

use App\Enums\CategoriaReembolso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ReembolsoRequest extends FormRequest
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
            'colaborador_id' => ['required', 'exists:colaboradores,id'],
            'conta_bancaria_id' => ['nullable', 'exists:contas_bancarias,id'],
            'descricao' => ['required', 'string', 'max:255'],
            'categoria' => ['required', new Enum(CategoriaReembolso::class)],
            'valor' => ['required', 'numeric', 'min:0.01', 'max:99999999999.99'],
            'data_solicitacao' => ['required', 'date', 'before_or_equal:today'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
            'comprovante' => [
                'nullable',
                'file',
                'max:'.config('payrollos.anexos.tamanho_maximo_kb'),
                'mimes:'.implode(',', config('payrollos.anexos.mimes')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'colaborador_id' => 'colaborador',
            'conta_bancaria_id' => 'conta de destino',
            'descricao' => 'descrição',
            'data_solicitacao' => 'data da solicitação',
        ];
    }
}
