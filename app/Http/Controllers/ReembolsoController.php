<?php

namespace App\Http\Controllers;

use App\Enums\CategoriaReembolso;
use App\Enums\StatusReembolso;
use App\Http\Requests\ReembolsoRequest;
use App\Models\Colaborador;
use App\Models\Reembolso;
use App\Services\AnexoService;
use App\Services\ReembolsoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReembolsoController extends Controller
{
    public function __construct(
        private readonly ReembolsoService $service,
        private readonly AnexoService $anexos,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Reembolso::class);

        $reembolsos = Reembolso::query()
            ->with('colaborador:id,nome,departamento')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('categoria'), fn ($q) => $q->where('categoria', $request->string('categoria')))
            ->when($request->filled('colaborador_id'), fn ($q) => $q->where('colaborador_id', $request->integer('colaborador_id')))
            ->noPeriodo($request->string('inicio')->toString() ?: null, $request->string('fim')->toString() ?: null)
            ->latest('data_solicitacao')
            ->paginate($request->integer('por_pagina', 20))
            ->withQueryString()
            ->through(fn (Reembolso $reembolso) => $this->paraListagem($reembolso));

        return Inertia::render('Reembolsos/Index', [
            'reembolsos' => $reembolsos,
            'filtros' => $request->only(['status', 'categoria', 'colaborador_id', 'inicio', 'fim']),
            'opcoes' => [
                'status' => StatusReembolso::opcoes(),
                'categorias' => CategoriaReembolso::opcoes(),
                // Todos, nao so os ativos: reembolso de quem ja foi desligado
                // continua na listagem e precisa ser filtravel.
                'colaboradores' => Colaborador::query()
                    ->orderBy('nome')
                    ->get(['id', 'nome', 'departamento']),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Reembolso::class);

        return Inertia::render('Reembolsos/Form', [
            'reembolso' => null,
            'opcoes' => $this->opcoesFormulario(),
        ]);
    }

    public function store(ReembolsoRequest $request): RedirectResponse
    {
        $this->authorize('create', Reembolso::class);

        $dados = $request->validated();
        $colaborador = Colaborador::findOrFail($dados['colaborador_id']);

        unset($dados['colaborador_id'], $dados['comprovante']);

        $reembolso = $this->service->solicitar($colaborador, $dados);

        if ($request->hasFile('comprovante')) {
            $this->anexos->anexar($reembolso, $request->file('comprovante'));
        }

        return redirect()
            ->route('reembolsos.show', $reembolso)
            ->with('sucesso', 'Solicitação de reembolso registrada.');
    }

    public function show(Reembolso $reembolso): Response
    {
        $this->authorize('view', $reembolso);

        $reembolso->load([
            'colaborador:id,nome,departamento,cpf',
            'contaBancaria',
            'anexos.enviadoPor:id,name',
            'historicoStatus.usuario:id,name',
        ]);

        return Inertia::render('Reembolsos/Show', [
            'reembolso' => $reembolso,
            'transicoesPermitidas' => array_map(
                fn (StatusReembolso $status) => ['value' => $status->value, 'label' => $status->rotulo()],
                $reembolso->status->transicoesPermitidas()
            ),
        ]);
    }

    public function edit(Reembolso $reembolso): Response
    {
        $this->authorize('update', $reembolso);

        return Inertia::render('Reembolsos/Form', [
            'reembolso' => $reembolso,
            'opcoes' => $this->opcoesFormulario(),
        ]);
    }

    public function update(ReembolsoRequest $request, Reembolso $reembolso): RedirectResponse
    {
        $this->authorize('update', $reembolso);

        $dados = $request->validated();
        unset($dados['comprovante']);

        $this->service->atualizar($reembolso, $dados);

        if ($request->hasFile('comprovante')) {
            $this->anexos->anexar($reembolso, $request->file('comprovante'));
        }

        return redirect()
            ->route('reembolsos.show', $reembolso)
            ->with('sucesso', 'Reembolso atualizado.');
    }

    public function alterarStatus(Request $request, Reembolso $reembolso): RedirectResponse
    {
        $dados = $request->validate([
            'status' => ['required', 'string'],
            'observacao' => ['nullable', 'string', 'max:1000'],
            'data_pagamento' => ['nullable', 'date'],
        ]);

        $novoStatus = StatusReembolso::from($dados['status']);

        // Aprovar e confirmar pagamento sao permissoes distintas (regra 3.7).
        $this->authorize(
            match ($novoStatus) {
                StatusReembolso::Aprovado => 'aprovar',
                StatusReembolso::Pago => 'confirmarPagamento',
                default => 'update',
            },
            $reembolso
        );

        $this->service->alterarStatus(
            $reembolso,
            $novoStatus,
            $dados['observacao'] ?? null,
            isset($dados['data_pagamento']) ? Carbon::parse($dados['data_pagamento']) : null,
        );

        return back()->with('sucesso', 'Status atualizado.');
    }

    public function destroy(Reembolso $reembolso): RedirectResponse
    {
        $this->authorize('delete', $reembolso);

        $reembolso->delete();

        return redirect()
            ->route('reembolsos.index')
            ->with('sucesso', 'Reembolso removido.');
    }

    /**
     * Recorte da listagem: datas viram AAAA-MM-DD para nao dependerem do fuso do
     * navegador, e a categoria ja sai rotulada. O model inteiro traria
     * observacoes e chaves estrangeiras que a grid nao usa.
     *
     * @return array<string, mixed>
     */
    private function paraListagem(Reembolso $reembolso): array
    {
        return [
            'id' => $reembolso->id,
            'descricao' => $reembolso->descricao,
            'colaborador_nome' => $reembolso->colaborador->nome,
            'colaborador_departamento' => $reembolso->colaborador->departamento,
            'categoria' => $reembolso->categoria->rotulo(),
            'valor' => $reembolso->valor,
            'data_solicitacao' => $reembolso->data_solicitacao->toDateString(),
            'status' => $reembolso->status->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function opcoesFormulario(): array
    {
        return [
            'categorias' => CategoriaReembolso::opcoes(),
            'colaboradores' => Colaborador::ativos()
                ->orderBy('nome')
                ->get(['id', 'nome', 'departamento']),
        ];
    }
}
