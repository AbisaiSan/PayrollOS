<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContaBancariaRequest;
use App\Models\Colaborador;
use App\Models\ContaBancaria;
use App\Models\Fornecedor;
use App\Services\ContaBancariaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

/**
 * Contas e chaves Pix de colaboradores e fornecedores (regra 3.2).
 *
 * O tipo do beneficiario vem na URL porque a tabela e compartilhada entre os dois.
 */
class ContaBancariaController extends Controller
{
    public function __construct(private readonly ContaBancariaService $service) {}

    public function store(ContaBancariaRequest $request, string $tipoBeneficiario, int $beneficiarioId): RedirectResponse
    {
        $beneficiario = $this->resolverBeneficiario($tipoBeneficiario, $beneficiarioId);

        $this->authorize('create', ContaBancaria::class);

        $this->service->criar($beneficiario, $request->validated());

        return back()->with('sucesso', 'Conta cadastrada.');
    }

    public function update(
        ContaBancariaRequest $request,
        string $tipoBeneficiario,
        int $beneficiarioId,
        ContaBancaria $conta,
    ): RedirectResponse {
        $this->garantirQueContaPertenceAoBeneficiario($conta, $tipoBeneficiario, $beneficiarioId);

        $this->authorize('update', $conta);

        $this->service->atualizar($conta, $request->validated());

        return back()->with('sucesso', 'Conta atualizada.');
    }

    public function definirPrincipal(string $tipoBeneficiario, int $beneficiarioId, ContaBancaria $conta): RedirectResponse
    {
        $this->garantirQueContaPertenceAoBeneficiario($conta, $tipoBeneficiario, $beneficiarioId);

        $this->authorize('update', $conta);

        $this->service->definirPrincipal($conta);

        return back()->with('sucesso', 'Conta definida como principal.');
    }

    public function inativar(string $tipoBeneficiario, int $beneficiarioId, ContaBancaria $conta): RedirectResponse
    {
        $this->garantirQueContaPertenceAoBeneficiario($conta, $tipoBeneficiario, $beneficiarioId);

        $this->authorize('inativar', $conta);

        $this->service->inativar($conta);

        return back()->with('sucesso', 'Conta inativada. Os pagamentos já lançados continuam vinculados a ela.');
    }

    public function reativar(string $tipoBeneficiario, int $beneficiarioId, ContaBancaria $conta): RedirectResponse
    {
        $this->garantirQueContaPertenceAoBeneficiario($conta, $tipoBeneficiario, $beneficiarioId);

        $this->authorize('update', $conta);

        $this->service->reativar($conta);

        return back()->with('sucesso', 'Conta reativada.');
    }

    private function resolverBeneficiario(string $tipo, int $id): Model
    {
        return match ($tipo) {
            'colaborador' => Colaborador::findOrFail($id),
            'fornecedor' => Fornecedor::findOrFail($id),
        };
    }

    /**
     * Impede que um id de conta de outro beneficiario seja manipulado pela URL.
     */
    private function garantirQueContaPertenceAoBeneficiario(ContaBancaria $conta, string $tipo, int $id): void
    {
        abort_unless(
            $conta->owner_type === $tipo && $conta->owner_id === $id,
            404
        );
    }
}
