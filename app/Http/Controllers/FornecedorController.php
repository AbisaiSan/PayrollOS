<?php

namespace App\Http\Controllers;

use App\Enums\StatusFornecedor;
use App\Enums\TipoChavePix;
use App\Enums\TipoConta;
use App\Enums\TipoFornecedor;
use App\Enums\TipoPessoa;
use App\Http\Requests\FornecedorRequest;
use App\Models\Fornecedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FornecedorController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Fornecedor::class);

        $fornecedores = Fornecedor::query()
            ->busca($request->string('busca')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('tipo_fornecedor'), fn ($q) => $q->where('tipo_fornecedor', $request->string('tipo_fornecedor')))
            // contasAtivas e nao contasBancarias, mesma razao da listagem de
            // colaboradores: conta inativa nao serve de destino de pagamento.
            ->withCount(['contratos', 'contasAtivas'])
            ->orderBy('razao_social')
            ->paginate($request->integer('por_pagina', 15))
            ->withQueryString();

        return Inertia::render('Fornecedores/Index', [
            'fornecedores' => $fornecedores,
            'filtros' => $request->only(['busca', 'status', 'tipo_fornecedor']),
            'opcoes' => [
                'status' => StatusFornecedor::opcoes(),
                'tipoFornecedor' => TipoFornecedor::opcoes(),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Fornecedor::class);

        return Inertia::render('Fornecedores/Form', [
            'fornecedor' => null,
            'opcoes' => $this->opcoesFormulario(),
        ]);
    }

    public function store(FornecedorRequest $request): RedirectResponse
    {
        $this->authorize('create', Fornecedor::class);

        $fornecedor = Fornecedor::create($request->validated());

        return redirect()
            ->route('fornecedores.show', $fornecedor)
            ->with('sucesso', 'Fornecedor cadastrado.');
    }

    public function show(Fornecedor $fornecedor): Response
    {
        $this->authorize('view', $fornecedor);

        $fornecedor->load(['contasBancarias', 'contratos.categoria:id,nome']);

        return Inertia::render('Fornecedores/Show', [
            'fornecedor' => $fornecedor,
            'pagamentos' => $fornecedor->pagamentos()
                ->with('categoria:id,nome')
                ->latest('data_vencimento')
                ->limit(20)
                ->get(),
            'opcoes' => [
                'tipoConta' => TipoConta::opcoes(),
                'tipoChavePix' => TipoChavePix::opcoes(),
            ],
        ]);
    }

    public function edit(Fornecedor $fornecedor): Response
    {
        $this->authorize('update', $fornecedor);

        return Inertia::render('Fornecedores/Form', [
            'fornecedor' => $fornecedor,
            'opcoes' => $this->opcoesFormulario(),
        ]);
    }

    public function update(FornecedorRequest $request, Fornecedor $fornecedor): RedirectResponse
    {
        $this->authorize('update', $fornecedor);

        $fornecedor->update($request->validated());

        return redirect()
            ->route('fornecedores.show', $fornecedor)
            ->with('sucesso', 'Fornecedor atualizado.');
    }

    public function destroy(Fornecedor $fornecedor): RedirectResponse
    {
        $this->authorize('delete', $fornecedor);

        $fornecedor->delete();

        return redirect()
            ->route('fornecedores.index')
            ->with('sucesso', 'Fornecedor removido da listagem. O histórico foi preservado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function opcoesFormulario(): array
    {
        return [
            'tipoPessoa' => TipoPessoa::opcoes(),
            'tipoFornecedor' => TipoFornecedor::opcoes(),
            'status' => StatusFornecedor::opcoes(),
        ];
    }
}
