<?php

namespace App\Http\Controllers;

use App\Enums\Periodicidade;
use App\Enums\StatusContrato;
use App\Enums\TipoContrato;
use App\Http\Requests\ContratoRequest;
use App\Models\CategoriaPagamento;
use App\Models\Contrato;
use App\Models\Fornecedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContratoController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Contrato::class);

        $contratos = Contrato::query()
            ->with(['fornecedor:id,razao_social,nome_fantasia', 'categoria:id,nome'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->string('tipo')))
            ->when($request->filled('fornecedor_id'), fn ($q) => $q->where('fornecedor_id', $request->integer('fornecedor_id')))
            ->orderBy('descricao')
            ->paginate($request->integer('por_pagina', 20))
            ->withQueryString()
            ->through(fn (Contrato $contrato) => $this->paraListagem($contrato));

        return Inertia::render('Contratos/Index', [
            'contratos' => $contratos,
            'filtros' => $request->only(['status', 'tipo', 'fornecedor_id']),
            'opcoes' => [
                'status' => StatusContrato::opcoes(),
                'tipo' => TipoContrato::opcoes(),
                // O filtro por fornecedor ja existia na consulta, mas a tela nao
                // recebia a lista para montar o select. Todos, nao so os ativos:
                // contrato de fornecedor inativo continua na listagem.
                'fornecedores' => Fornecedor::query()
                    ->orderBy('razao_social')
                    ->get(['id', 'razao_social', 'nome_fantasia']),
            ],
        ]);
    }

    /**
     * Recorte da listagem: datas viram AAAA-MM-DD para nao dependerem do fuso do
     * navegador, e tipo e periodicidade ja saem rotulados.
     *
     * @return array<string, mixed>
     */
    private function paraListagem(Contrato $contrato): array
    {
        return [
            'id' => $contrato->id,
            'descricao' => $contrato->descricao,
            'categoria' => $contrato->categoria?->nome,
            'fornecedor' => $contrato->fornecedor->nome_exibicao,
            'tipo' => $contrato->tipo->value,
            'tipo_rotulo' => $contrato->tipo->rotulo(),
            'periodicidade_rotulo' => $contrato->periodicidade?->rotulo(),
            'dia_vencimento' => $contrato->dia_vencimento,
            'valor' => $contrato->valor,
            'proximo_vencimento' => $contrato->proximo_vencimento?->toDateString(),
            'status' => $contrato->status->value,
        ];
    }

    public function create(): Response
    {
        $this->authorize('create', Contrato::class);

        return Inertia::render('Contratos/Form', [
            'contrato' => null,
            'opcoes' => $this->opcoesFormulario(),
        ]);
    }

    public function store(ContratoRequest $request): RedirectResponse
    {
        $this->authorize('create', Contrato::class);

        $contrato = Contrato::create($request->validated());

        return redirect()
            ->route('contratos.show', $contrato)
            ->with('sucesso', 'Contrato cadastrado.');
    }

    public function show(Contrato $contrato): Response
    {
        $this->authorize('view', $contrato);

        $contrato->load(['fornecedor', 'categoria:id,nome', 'contaBancaria', 'anexos']);

        return Inertia::render('Contratos/Show', [
            'contrato' => $contrato,
            'pagamentosGerados' => $contrato->pagamentos()
                ->latest('data_vencimento')
                ->limit(20)
                ->get(['id', 'descricao', 'valor', 'data_vencimento', 'status']),
        ]);
    }

    public function edit(Contrato $contrato): Response
    {
        $this->authorize('update', $contrato);

        return Inertia::render('Contratos/Form', [
            'contrato' => $contrato,
            'opcoes' => $this->opcoesFormulario($contrato),
        ]);
    }

    public function update(ContratoRequest $request, Contrato $contrato): RedirectResponse
    {
        $this->authorize('update', $contrato);

        $contrato->update($request->validated());

        return redirect()
            ->route('contratos.show', $contrato)
            ->with('sucesso', 'Contrato atualizado.');
    }

    public function destroy(Contrato $contrato): RedirectResponse
    {
        $this->authorize('delete', $contrato);

        $contrato->update(['status' => StatusContrato::Encerrado]);

        return redirect()
            ->route('contratos.index')
            ->with('sucesso', 'Contrato encerrado. Novos lançamentos automáticos não serão mais gerados.');
    }

    /**
     * @return array<string, mixed>
     */
    private function opcoesFormulario(?Contrato $contrato = null): array
    {
        $fornecedores = Fornecedor::ativos()
            ->orderBy('razao_social')
            ->get(['id', 'razao_social', 'nome_fantasia']);

        // Ao editar, o fornecedor do contrato entra na lista mesmo se ja tiver
        // sido inativado. Sem isso o campo abre em branco e parece que ninguem
        // esta escolhido, embora o vinculo continue intacto.
        if ($contrato && ! $fornecedores->contains('id', $contrato->fornecedor_id)) {
            $fornecedores = $fornecedores
                ->push($contrato->fornecedor()->first(['id', 'razao_social', 'nome_fantasia']))
                ->sortBy('razao_social')
                ->values();
        }

        return [
            'fornecedores' => $fornecedores,
            'categorias' => CategoriaPagamento::ativas()->orderBy('nome')->get(['id', 'nome']),
            'tipo' => TipoContrato::opcoes(),
            'periodicidade' => Periodicidade::opcoes(),
            'status' => StatusContrato::opcoes(),
        ];
    }
}
