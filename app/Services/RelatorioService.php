<?php

namespace App\Services;

use App\Enums\StatusPagamento;
use App\Enums\StatusReembolso;
use App\Models\Colaborador;
use App\Models\Fornecedor;
use App\Models\Pagamento;
use App\Models\Reembolso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Consolidacao por periodo (Fase 6 do plano).
 *
 * Junta pagamentos e reembolsos, como manda a regra 3.7: o reembolso "aparece de
 * forma consolidada nos relatorios de pagamento junto com folha e fornecedores".
 * Ate a tarefa 2 do backend o relatorio lia so a tabela `pagamentos`, e como
 * pagar um reembolso nao cria linha nenhuma la, nenhum reembolso jamais entrou em
 * relatorio — o total do periodo saia menor que a realidade.
 *
 * Existe como service, e nao como metodo privado do controller, porque a tela e o
 * arquivo exportado precisam sair do mesmo lugar. O modal promete "mantem os
 * filtros aplicados na tela"; duas consultas separadas divergiriam na primeira
 * mudanca de regra em so uma delas.
 */
class RelatorioService
{
    /**
     * Estados que representam dinheiro que nao vai sair.
     *
     * Ficam de fora do total e da quebra por categoria, e continuam visiveis na
     * quebra por status — some do numero que se usa para fechar o mes, nao da
     * tela. Somar um lancamento cancelado ou um reembolso rejeitado ao "total do
     * periodo" responde errado a unica pergunta que o relatorio existe para
     * responder.
     */
    private const NAO_REALIZAVEIS = [
        StatusPagamento::Cancelado->value,
        StatusReembolso::Rejeitado->value,
    ];

    /**
     * Normaliza os filtros da requisicao.
     *
     * Sem periodo, o mes corrente. A tela mostra qual intervalo esta em uso
     * justamente porque essa escolha e do backend, nao do usuario.
     *
     * @return array{inicio: string, fim: string, categoria_id: ?int, status: ?string}
     */
    public function filtros(Request $request): array
    {
        return [
            'inicio' => $request->string('inicio')->toString() ?: now()->startOfMonth()->toDateString(),
            'fim' => $request->string('fim')->toString() ?: now()->endOfMonth()->toDateString(),
            'categoria_id' => $request->integer('categoria_id') ?: null,
            'status' => $request->string('status')->toString() ?: null,
        ];
    }

    /**
     * @param  array{inicio: string, fim: string, categoria_id: ?int, status: ?string}  $filtros
     */
    public function consultaPagamentos(array $filtros): Builder
    {
        return Pagamento::query()
            ->noPeriodo($filtros['inicio'], $filtros['fim'])
            ->when($filtros['categoria_id'], fn ($q, $id) => $q->where('categoria_id', $id))
            ->when($filtros['status'], fn ($q, $status) => $q->where('status', $status));
    }

    /**
     * O periodo do reembolso e a data de solicitacao — nao existe vencimento nele.
     *
     * Filtrar por categoria zera os reembolsos de proposito: `categoria_id` aponta
     * para a tabela de categorias de pagamento, e reembolso usa um enum proprio.
     * Trazer reembolso num recorte de "categoria Aluguel" seria mentira.
     *
     * @param  array{inicio: string, fim: string, categoria_id: ?int, status: ?string}  $filtros
     */
    public function consultaReembolsos(array $filtros): Builder
    {
        return Reembolso::query()
            ->noPeriodo($filtros['inicio'], $filtros['fim'])
            ->when($filtros['categoria_id'], fn ($q) => $q->whereRaw('1 = 0'))
            ->when($filtros['status'], fn ($q, $status) => $q->where('status', $status));
    }

    /**
     * @param  array{inicio: string, fim: string, categoria_id: ?int, status: ?string}  $filtros
     * @return array{total: float, quantidade: int, naoRealizavel: float}
     */
    public function resumo(array $filtros): array
    {
        $pagamentos = $this->consultaPagamentos($filtros);
        $reembolsos = $this->consultaReembolsos($filtros);

        $realizavel = fn (Builder $consulta) => $consulta->whereNotIn('status', self::NAO_REALIZAVEIS);

        return [
            'total' => (float) $realizavel(clone $pagamentos)->sum('valor')
                + (float) $realizavel(clone $reembolsos)->sum('valor'),
            'quantidade' => $realizavel(clone $pagamentos)->count()
                + $realizavel(clone $reembolsos)->count(),
            // O que ficou de fora do total, para o numero excluido nao sumir sem aviso.
            'naoRealizavel' => (float) (clone $pagamentos)->whereIn('status', self::NAO_REALIZAVEIS)->sum('valor')
                + (float) (clone $reembolsos)->whereIn('status', self::NAO_REALIZAVEIS)->sum('valor'),
        ];
    }

    /**
     * Pagamento e reembolso compartilham "Pendente" e "Pago", e a linha soma os
     * dois: e disso que "consolidado" trata. "Aprovado" e "Rejeitado" so existem
     * em reembolso, "Agendado" e "Atrasado" so em pagamento — cada um aparece
     * quando houver.
     *
     * @param  array{inicio: string, fim: string, categoria_id: ?int, status: ?string}  $filtros
     * @return Collection<int, array<string, mixed>>
     */
    public function porStatus(array $filtros): Collection
    {
        $agrupar = fn (Builder $consulta) => $consulta
            ->groupBy('status')
            ->select('status', DB::raw('SUM(valor) as total'), DB::raw('COUNT(*) as quantidade'))
            ->get();

        return $agrupar($this->consultaPagamentos($filtros))
            ->concat($agrupar($this->consultaReembolsos($filtros)))
            ->groupBy(fn ($linha) => $linha->status->value)
            ->map(fn (Collection $linhas, string $status) => [
                'status' => $status,
                'rotulo' => $linhas->first()->status->rotulo(),
                'total' => (float) $linhas->sum('total'),
                'quantidade' => (int) $linhas->sum('quantidade'),
                'realizavel' => ! in_array($status, self::NAO_REALIZAVEIS, true),
            ])
            ->sortByDesc('total')
            ->values();
    }

    /**
     * Reembolso nao tem categoria_id, tem enum proprio. Entra como
     * "Reembolso — Viagem" em vez de virar um balde unico: o detalhe da despesa e
     * o que torna a quebra util para quem esta olhando gasto de viagem.
     *
     * @param  array{inicio: string, fim: string, categoria_id: ?int, status: ?string}  $filtros
     * @return Collection<int, array<string, mixed>>
     */
    public function porCategoria(array $filtros): Collection
    {
        $dePagamentos = $this->consultaPagamentos($filtros)
            ->whereNotIn('pagamentos.status', self::NAO_REALIZAVEIS)
            ->join('categorias_pagamento', 'categorias_pagamento.id', '=', 'pagamentos.categoria_id')
            ->groupBy('categorias_pagamento.nome')
            ->select('categorias_pagamento.nome', DB::raw('SUM(pagamentos.valor) as total'))
            ->get()
            ->map(fn ($linha) => ['nome' => $linha->nome, 'total' => (float) $linha->total]);

        $deReembolsos = $this->consultaReembolsos($filtros)
            ->whereNotIn('status', self::NAO_REALIZAVEIS)
            ->groupBy('categoria')
            ->select('categoria', DB::raw('SUM(valor) as total'))
            ->get()
            ->map(fn ($linha) => [
                'nome' => 'Reembolso — '.$linha->categoria->rotulo(),
                'total' => (float) $linha->total,
            ]);

        return $dePagamentos->concat($deReembolsos)->sortByDesc('total')->values();
    }

    /**
     * Os lancamentos que sustentam os agregados, pagamentos e reembolsos juntos.
     *
     * Sao eles que dao ao arquivo exportado utilidade que a tela nao tem: a tela
     * responde "quanto"; o arquivo precisa responder "quais", senao nao serve para
     * conferir nada. Aqui entram tambem os nao realizaveis, porque conferencia
     * precisa enxergar o que foi cancelado ou rejeitado.
     *
     * @param  array{inicio: string, fim: string, categoria_id: ?int, status: ?string}  $filtros
     * @return Collection<int, array<string, mixed>>
     */
    public function lancamentos(array $filtros): Collection
    {
        $pagamentos = $this->consultaPagamentos($filtros)
            ->with(['categoria:id,nome', 'payable'])
            ->get()
            ->map(fn (Pagamento $pagamento) => [
                'origem' => 'Pagamento',
                'id' => $pagamento->id,
                'descricao' => $pagamento->descricao,
                'beneficiario' => $this->nomeBeneficiario($pagamento),
                'beneficiario_tipo' => $pagamento->payable_type === 'colaborador'
                    ? 'Colaborador'
                    : 'Fornecedor',
                'categoria' => $pagamento->categoria?->nome ?? '—',
                'competencia' => $pagamento->competencia,
                'data' => $pagamento->data_vencimento,
                'data_pagamento' => $pagamento->data_pagamento,
                'forma_pagamento' => $pagamento->forma_pagamento->rotulo(),
                'status' => $pagamento->status->rotulo(),
                'realizavel' => ! in_array($pagamento->status->value, self::NAO_REALIZAVEIS, true),
                'valor' => (float) $pagamento->valor,
            ]);

        $reembolsos = $this->consultaReembolsos($filtros)
            ->with('colaborador:id,nome')
            ->get()
            ->map(fn (Reembolso $reembolso) => [
                'origem' => 'Reembolso',
                'id' => $reembolso->id,
                'descricao' => $reembolso->descricao,
                'beneficiario' => $reembolso->colaborador->nome,
                'beneficiario_tipo' => 'Colaborador',
                'categoria' => 'Reembolso — '.$reembolso->categoria->rotulo(),
                'competencia' => null,
                // A data do reembolso e a de solicitacao; ele nao tem vencimento.
                'data' => $reembolso->data_solicitacao,
                'data_pagamento' => $reembolso->data_pagamento,
                'forma_pagamento' => '—',
                'status' => $reembolso->status->rotulo(),
                'realizavel' => ! in_array($reembolso->status->value, self::NAO_REALIZAVEIS, true),
                'valor' => (float) $reembolso->valor,
            ]);

        return $pagamentos
            ->concat($reembolsos)
            ->sortBy('data')
            ->values()
            ->map(fn (array $linha) => [
                ...$linha,
                'data' => $linha['data']->format('d/m/Y'),
                'data_pagamento' => $linha['data_pagamento']?->format('d/m/Y'),
            ]);
    }

    /**
     * Os status oferecidos no filtro: os de pagamento mais os que so existem em
     * reembolso. Sem os segundos nao daria para recortar o relatorio consolidado
     * por "Aprovado", que agora aparece nele.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function opcoesDeStatus(): array
    {
        $status = collect(StatusPagamento::cases())
            ->concat(StatusReembolso::cases())
            ->unique(fn ($caso) => $caso->value)
            ->map(fn ($caso) => ['value' => $caso->value, 'label' => $caso->rotulo()]);

        return $status->values()->all();
    }

    private function nomeBeneficiario(Pagamento $pagamento): string
    {
        $beneficiario = $pagamento->payable;

        return match (true) {
            $beneficiario instanceof Colaborador => $beneficiario->nome,
            $beneficiario instanceof Fornecedor => $beneficiario->nome_exibicao,
            default => '—',
        };
    }
}
