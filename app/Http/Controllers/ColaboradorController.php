<?php

namespace App\Http\Controllers;

use App\Enums\StatusColaborador;
use App\Enums\TipoChavePix;
use App\Enums\TipoConta;
use App\Enums\TipoContratacao;
use App\Http\Requests\ColaboradorRequest;
use App\Models\Colaborador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ColaboradorController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Colaborador::class);

        $colaboradores = Colaborador::query()
            ->busca($request->string('busca')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('departamento'), fn ($q) => $q->where('departamento', $request->string('departamento')))
            // Contas ativas, nao todas: uma conta inativa nao serve de destino, o
            // PagamentoService a recusa. Contar todas mostraria "1" tranquilo para
            // quem, na pratica, nao tem como receber a folha.
            ->withCount('contasAtivas')
            ->orderBy($request->string('ordenar_por', 'nome')->toString(), $request->string('direcao', 'asc')->toString())
            ->paginate($request->integer('por_pagina', 15))
            ->withQueryString();

        return Inertia::render('Colaboradores/Index', [
            'colaboradores' => $colaboradores,
            'filtros' => $request->only(['busca', 'status', 'departamento', 'ordenar_por', 'direcao']),
            'opcoes' => [
                'status' => StatusColaborador::opcoes(),
                'departamentos' => Colaborador::query()
                    ->select('departamento')
                    ->distinct()
                    ->orderBy('departamento')
                    ->pluck('departamento'),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Colaborador::class);

        return Inertia::render('Colaboradores/Form', [
            'colaborador' => null,
            'opcoes' => $this->opcoesFormulario(),
        ]);
    }

    public function store(ColaboradorRequest $request): RedirectResponse
    {
        $this->authorize('create', Colaborador::class);

        $colaborador = Colaborador::create($request->validated());

        return redirect()
            ->route('colaboradores.show', $colaborador)
            ->with('sucesso', 'Colaborador cadastrado.');
    }

    public function show(Colaborador $colaborador): Response
    {
        $this->authorize('view', $colaborador);

        $colaborador->load(['contasBancarias', 'usuario:id,name,email,colaborador_id']);

        return Inertia::render('Colaboradores/Show', [
            'colaborador' => $colaborador,
            'pagamentos' => $colaborador->pagamentos()
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

    public function edit(Colaborador $colaborador): Response
    {
        $this->authorize('update', $colaborador);

        return Inertia::render('Colaboradores/Form', [
            'colaborador' => $colaborador,
            'opcoes' => $this->opcoesFormulario(),
        ]);
    }

    public function update(ColaboradorRequest $request, Colaborador $colaborador): RedirectResponse
    {
        $this->authorize('update', $colaborador);

        $colaborador->update($request->validated());

        return redirect()
            ->route('colaboradores.show', $colaborador)
            ->with('sucesso', 'Colaborador atualizado.');
    }

    /**
     * Regra 3.1: desligar nao apaga historico, so bloqueia novos lancamentos de
     * folha a partir da data informada.
     */
    public function desligar(Request $request, Colaborador $colaborador): RedirectResponse
    {
        $this->authorize('desligar', $colaborador);

        $dados = $request->validate([
            'data_desligamento' => ['required', 'date', 'after_or_equal:'.$colaborador->data_admissao->toDateString()],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($colaborador, $dados) {
            $colaborador->update([
                'status' => StatusColaborador::Desligado,
                'data_desligamento' => $dados['data_desligamento'],
                'observacoes' => $dados['observacoes'] ?? $colaborador->observacoes,
            ]);
        });

        return back()->with(
            'sucesso',
            'Colaborador desligado. O histórico de pagamentos foi mantido; lance a rescisão como pagamento avulso, se houver.'
        );
    }

    /**
     * Soft delete: o vinculo com os pagamentos ja lancados continua rastreavel.
     */
    public function destroy(Colaborador $colaborador): RedirectResponse
    {
        $this->authorize('delete', $colaborador);

        $colaborador->delete();

        return redirect()
            ->route('colaboradores.index')
            ->with('sucesso', 'Colaborador removido da listagem. O histórico foi preservado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function opcoesFormulario(): array
    {
        return [
            'tipoContrato' => TipoContratacao::opcoes(),
            'status' => StatusColaborador::opcoes(),
        ];
    }
}
