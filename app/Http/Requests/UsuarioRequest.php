<?php

namespace App\Http\Requests;

use App\Support\Perfis;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioRequest extends FormRequest
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
        $usuarioId = $this->route('usuario')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($usuarioId),
            ],
            // Na criacao a senha e obrigatoria; ao editar, em branco mantem a atual.
            'password' => [
                $usuarioId ? 'nullable' : 'required',
                'confirmed',
                Password::defaults(),
            ],
            'perfil' => ['required', Rule::in(Perfis::todos())],
            // Vinculo opcional: nem todo usuario e colaborador da folha, e um
            // colaborador nao pode responder por duas contas.
            'colaborador_id' => [
                'nullable',
                'exists:colaboradores,id',
                Rule::unique('users', 'colaborador_id')->ignore($usuarioId),
            ],
            'ativo' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['ativo' => $this->boolean('ativo')]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'email' => 'e-mail',
            'password' => 'senha',
            'perfil' => 'perfil',
            'colaborador_id' => 'colaborador vinculado',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'colaborador_id.unique' => 'Este colaborador já está vinculado a outra conta de acesso.',
        ];
    }
}
