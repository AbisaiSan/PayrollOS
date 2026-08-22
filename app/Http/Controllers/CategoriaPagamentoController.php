<?php

namespace App\Http\Controllers;

use App\Enums\TipoCategoria;
use App\Http\Requests\CategoriaPagamentoRequest;
use App\Models\CategoriaPagamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Regra 3.5: categorias sao cadastraveis, o financeiro ajusta sem depender de deploy.
 */
class CategoriaPagamentoController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CategoriaPagamento::class);

        return Inertia::render('Categorias/Index', [
            'categorias' => CategoriaPagamento::query()
                ->withCount('pagamentos')
                ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->string('tipo')))
                ->orderBy('nome')
                ->get(),
            'filtros' => $request->only(['tipo']),
            'opcoes' => [
                'tipo' => TipoCategoria::opcoes(),
            ],
        ]);
    }

    public function store(CategoriaPagamentoRequest $request): RedirectResponse
    {
        $this->authorize('create', CategoriaPagamento::class);

        CategoriaPagamento::create($request->validated());

        return redirect()
            ->route('categorias.index')
            ->with('sucesso', 'Categoria criada.');
    }

    public function update(CategoriaPagamentoRequest $request, CategoriaPagamento $categoria): RedirectResponse
    {
        $this->authorize('update', $categoria);

        $categoria->update($request->validated());

        return redirect()
            ->route('categorias.index')
            ->with('sucesso', 'Categoria atualizada.');
    }

    /**
     * Categoria ja usada em algum pagamento so pode ser desativada: excluir
     * quebraria o relatorio historico. A policy barra esse caso.
     */
    public function destroy(CategoriaPagamento $categoria): RedirectResponse
    {
        $this->authorize('delete', $categoria);

        $categoria->delete();

        return redirect()
            ->route('categorias.index')
            ->with('sucesso', 'Categoria excluída.');
    }
}
