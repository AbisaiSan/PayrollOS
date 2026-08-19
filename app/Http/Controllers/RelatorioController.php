<?php

namespace App\Http\Controllers;

use App\Enums\StatusPagamento;
use App\Models\CategoriaPagamento;
use App\Models\Pagamento;
use App\Support\Permissoes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Fase 6 do plano. A consolidacao junta folha, fornecedores e reembolsos.
 *
 * A exportacao Excel/PDF (maatwebsite/excel e dompdf, ja instalados) entra na
 * Fase 6; a rota existe para nao mudar o contrato depois.
 */
class RelatorioController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can(Permissoes::RELATORIOS_VER), 403);

        $inicio = $request->string('inicio')->toString() ?: now()->startOfMonth()->toDateString();
        $fim = $request->string('fim')->toString() ?: now()->endOfMonth()->toDateString();

        $base = Pagamento::query()
            ->noPeriodo($inicio, $fim)
            ->when($request->filled('categoria_id'), fn ($q) => $q->where('categoria_id', $request->integer('categoria_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        return Inertia::render('Relatorios/Index', [
            'filtros' => [
                'inicio' => $inicio,
                'fim' => $fim,
                'categoria_id' => $request->integer('categoria_id') ?: null,
                'status' => $request->string('status')->toString() ?: null,
            ],
            'resumo' => [
                'total' => (float) (clone $base)->sum('valor'),
                'quantidade' => (clone $base)->count(),
            ],
            'porStatus' => (clone $base)
                ->groupBy('status')
                ->select('status', DB::raw('SUM(valor) as total'), DB::raw('COUNT(*) as quantidade'))
                ->get()
                ->map(fn ($linha) => [
                    'status' => $linha->status->value,
                    'rotulo' => $linha->status->rotulo(),
                    'total' => (float) $linha->total,
                    'quantidade' => (int) $linha->quantidade,
                ]),
            'porCategoria' => (clone $base)
                ->join('categorias_pagamento', 'categorias_pagamento.id', '=', 'pagamentos.categoria_id')
                ->groupBy('categorias_pagamento.nome')
                ->select('categorias_pagamento.nome', DB::raw('SUM(pagamentos.valor) as total'))
                ->orderByDesc('total')
                ->get()
                ->map(fn ($linha) => ['nome' => $linha->nome, 'total' => (float) $linha->total]),
            'opcoes' => [
                'categorias' => CategoriaPagamento::ativas()->orderBy('nome')->get(['id', 'nome']),
                'status' => StatusPagamento::opcoes(),
            ],
        ]);
    }

    public function exportar(Request $request)
    {
        abort_unless($request->user()->can(Permissoes::RELATORIOS_EXPORTAR), 403);

        // TODO (Fase 6): exportacao Excel via maatwebsite/excel e PDF via dompdf.
        abort(501, 'Exportação será implementada na Fase 6.');
    }
}
