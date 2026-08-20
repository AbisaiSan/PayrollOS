<?php

namespace App\Http\Controllers;

use App\Enums\FormaPagamento;
use App\Enums\StatusPagamento;
use App\Http\Requests\PagamentoRequest;
use App\Models\CategoriaPagamento;
use App\Models\Colaborador;
use App\Models\Fornecedor;
use App\Models\Pagamento;
use App\Services\PagamentoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Modulo central (regra 3.6).
 */
class PagamentoController extends Controller
{
    public function __construct(private readonly PagamentoService $service) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Pagamento::class);

        $pagamentos = Pagamento::query()
            ->with(['categoria:id,nome', 'payable', 'contaBancaria:id,banco,agencia,conta,digito'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('categoria_id'), fn ($q) => $q->where('categoria_id', $request->integer('categoria_id')))
            ->when($request->filled('competencia'), fn ($q) => $q->daCompetencia($request->string('competencia')->toString()))
            ->when($request->filled('beneficiario_tipo'), fn ($q) => $q->where('payable_type', $request->string('beneficiario_tipo')))
            ->when($request->filled('beneficiario_id'), fn ($q) => $q->where('payable_id', $request->integer('beneficiario_id')))
            // LOWER dos dois lados: LIKE no PostgreSQL e sensivel a caixa.
            ->when($request->filled('busca'), fn ($q) => $q->whereRaw(
                'LOWER(descricao) LIKE ?',
                ['%'.mb_strtolower($request->string('busca')->toString()).'%']
            ))
            ->noPeriodo($request->string('inicio')->toString() ?: null, $request->string('fim')->toString() ?: null)
            ->orderBy($request->string('ordenar_por', 'data_vencimento')->toString(), $request->string('direcao', 'asc')->toString())
            ->paginate($request->integer('por_pagina', 20))
            ->withQueryString()
            ->through(fn (Pagamento $pagamento) => $this->paraListagem($pagamento));

        return Inertia::render('Pagamentos/Index', [
            'pagamentos' => $pagamentos,
            'filtros' => $request->only([
                'status', 'categoria_id', 'competencia', 'beneficiario_tipo',
                'beneficiario_id', 'busca', 'inicio', 'fim',
            ]),
            'opcoes' => [
                'status' => StatusPagamento::opcoes(),
                'categorias' => CategoriaPagamento::ativas()->orderBy('nome')->get(['id', 'nome']),
            ],
            'totais' => [
                'emAberto' => (float) Pagamento::query()->emAberto()->sum('valor'),
                'atrasado' => (float) Pagamento::query()->where('status', StatusPagamento::Atrasado)->sum('valor'),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Pagamento::class);

        return Inertia::render('Pagamentos/Form', [
            'pagamento' => null,
            'opcoes' => $this->opcoesFormulario(),
        ]);
    }

    public function store(PagamentoRequest $request): RedirectResponse
    {
        $this->authorize('create', Pagamento::class);

        $dados = $request->validated();
        $beneficiario = $this->resolverBeneficiario($dados['payable_type'], (int) $dados['payable_id']);

        unset($dados['payable_type'], $dados['payable_id']);

        $pagamento = $this->service->lancar($beneficiario, $dados);

        return redirect()
            ->route('pagamentos.show', $pagamento)
            ->with('sucesso', 'Lançamento criado.');
    }

    public function show(Pagamento $pagamento): Response
    {
        $this->authorize('view', $pagamento);

        $pagamento->load([
            'categoria:id,nome',
            'payable',
            'contaBancaria',
            'contrato:id,descricao',
            'anexos.enviadoPor:id,name',
            'historicoStatus.usuario:id,name',
            'criadoPor:id,name',
        ]);

        return Inertia::render('Pagamentos/Show', [
            'pagamento' => [
                ...$pagamento->toArray(),
                'beneficiario_nome' => $this->nomeBeneficiario($pagamento),
            ],
            // O frontend so oferece as transicoes que o backend aceitaria.
            'transicoesPermitidas' => array_map(
                fn (StatusPagamento $status) => ['value' => $status->value, 'label' => $status->rotulo()],
                $pagamento->status->transicoesPermitidas()
            ),
        ]);
    }

    public function edit(Pagamento $pagamento): Response
    {
        $this->authorize('update', $pagamento);

        $pagamento->load('payable');

        return Inertia::render('Pagamentos/Form', [
            'pagamento' => [
                ...$pagamento->toArray(),
                'beneficiario_nome' => $this->nomeBeneficiario($pagamento),
            ],
            'opcoes' => $this->opcoesFormulario(),
        ]);
    }

    public function update(PagamentoRequest $request, Pagamento $pagamento): RedirectResponse
    {
        $this->authorize('update', $pagamento);

        $dados = $request->validated();
        unset($dados['payable_type'], $dados['payable_id']);

        $this->service->atualizar($pagamento, $dados);

        return redirect()
            ->route('pagamentos.show', $pagamento)
            ->with('sucesso', 'Lançamento atualizado.');
    }

    /**
     * Regra 3.6: a mudanca de status passa pelo service, que valida a transicao e
     * grava o historico de auditoria.
     */
    public function alterarStatus(Request $request, Pagamento $pagamento): RedirectResponse
    {
        $this->authorize('alterarStatus', $pagamento);

        $dados = $request->validate([
            'status' => ['required', 'string'],
            'observacao' => ['nullable', 'string', 'max:1000'],
            'data_pagamento' => ['nullable', 'date'],
        ]);

        $this->service->alterarStatus(
            $pagamento,
            StatusPagamento::from($dados['status']),
            $dados['observacao'] ?? null,
            isset($dados['data_pagamento']) ? Carbon::parse($dados['data_pagamento']) : null,
        );

        return back()->with('sucesso', 'Status atualizado.');
    }

    /**
     * Confirmacao manual de que o pagamento saiu. O sistema nao executa nada.
     */
    public function confirmar(Request $request, Pagamento $pagamento): RedirectResponse
    {
        $this->authorize('confirmar', $pagamento);

        $dados = $request->validate([
            'data_pagamento' => ['required', 'date', 'before_or_equal:today'],
            'observacao' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->confirmarPagamento(
            $pagamento,
            Carbon::parse($dados['data_pagamento']),
            $dados['observacao'] ?? null,
        );

        return back()->with('sucesso', 'Pagamento confirmado.');
    }

    public function destroy(Pagamento $pagamento): RedirectResponse
    {
        $this->authorize('delete', $pagamento);

        $this->service->cancelar($pagamento, 'Lançamento cancelado pelo usuário.');

        return redirect()
            ->route('pagamentos.index')
            ->with('sucesso', 'Lançamento cancelado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function paraListagem(Pagamento $pagamento): array
    {
        return [
            'id' => $pagamento->id,
            'descricao' => $pagamento->descricao,
            'beneficiario_nome' => $this->nomeBeneficiario($pagamento),
            'beneficiario_tipo' => $pagamento->payable_type,
            'categoria' => $pagamento->categoria?->nome,
            'competencia' => $pagamento->competencia,
            'valor' => $pagamento->valor,
            'data_vencimento' => $pagamento->data_vencimento->toDateString(),
            'data_pagamento' => $pagamento->data_pagamento?->toDateString(),
            'forma_pagamento' => $pagamento->forma_pagamento->value,
            'status' => $pagamento->status->value,
            'conta_destino' => $pagamento->contaBancaria?->resumo,
        ];
    }

    private function nomeBeneficiario(Pagamento $pagamento): string
    {
        $beneficiario = $pagamento->payable;

        if ($beneficiario instanceof Colaborador) {
            return $beneficiario->nome;
        }

        if ($beneficiario instanceof Fornecedor) {
            return $beneficiario->nome_exibicao;
        }

        return '—';
    }

    private function resolverBeneficiario(string $tipo, int $id): Model
    {
        return match ($tipo) {
            'colaborador' => Colaborador::findOrFail($id),
            'fornecedor' => Fornecedor::findOrFail($id),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function opcoesFormulario(): array
    {
        return [
            'categorias' => CategoriaPagamento::ativas()->orderBy('nome')->get(['id', 'nome', 'tipo']),
            'formaPagamento' => FormaPagamento::opcoes(),
            'status' => [
                ['value' => StatusPagamento::Pendente->value, 'label' => StatusPagamento::Pendente->rotulo()],
                ['value' => StatusPagamento::Agendado->value, 'label' => StatusPagamento::Agendado->rotulo()],
            ],
        ];
    }
}
