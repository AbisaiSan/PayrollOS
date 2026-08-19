<?php

namespace App\Http\Controllers;

use App\Enums\StatusPagamento;
use App\Enums\StatusReembolso;
use App\Models\Pagamento;
use App\Models\Reembolso;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Indicadores do mes corrente (Fase 6 do plano).
 */
class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $inicioMes = now()->startOfMonth();
        $fimMes = now()->endOfMonth();

        return Inertia::render('Dashboard', [
            'indicadores' => [
                'aPagarNoMes' => (float) Pagamento::query()
                    ->emAberto()
                    ->whereBetween('data_vencimento', [$inicioMes, $fimMes])
                    ->sum('valor'),

                'pagoNoMes' => (float) Pagamento::query()
                    ->where('status', StatusPagamento::Pago)
                    ->whereBetween('data_pagamento', [$inicioMes, $fimMes])
                    ->sum('valor'),

                'atrasados' => [
                    'quantidade' => Pagamento::query()
                        ->where('status', StatusPagamento::Atrasado)
                        ->count(),
                    'valor' => (float) Pagamento::query()
                        ->where('status', StatusPagamento::Atrasado)
                        ->sum('valor'),
                ],

                'reembolsosPendentes' => [
                    'quantidade' => Reembolso::query()
                        ->where('status', StatusReembolso::Pendente)
                        ->count(),
                    'valor' => (float) Reembolso::query()
                        ->where('status', StatusReembolso::Pendente)
                        ->sum('valor'),
                ],
            ],

            'porCategoria' => Pagamento::query()
                ->emAberto()
                ->whereBetween('data_vencimento', [$inicioMes, $fimMes])
                ->join('categorias_pagamento', 'categorias_pagamento.id', '=', 'pagamentos.categoria_id')
                ->groupBy('categorias_pagamento.nome')
                ->select('categorias_pagamento.nome', DB::raw('SUM(pagamentos.valor) as total'))
                ->orderByDesc('total')
                ->get()
                ->map(fn ($linha) => ['nome' => $linha->nome, 'total' => (float) $linha->total]),

            // Os proximos 7 dias sao o que o financeiro precisa ver ao abrir o sistema.
            'proximosVencimentos' => Pagamento::query()
                ->emAberto()
                ->with(['categoria:id,nome', 'payable'])
                ->whereBetween('data_vencimento', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
                ->orderBy('data_vencimento')
                ->limit(10)
                ->get()
                ->map(fn (Pagamento $pagamento) => [
                    'id' => $pagamento->id,
                    'descricao' => $pagamento->descricao,
                    'beneficiario' => $this->nomeBeneficiario($pagamento),
                    'categoria' => $pagamento->categoria?->nome,
                    'valor' => $pagamento->valor,
                    'data_vencimento' => $pagamento->data_vencimento->toDateString(),
                    'status' => $pagamento->status->value,
                ]),
        ]);
    }

    private function nomeBeneficiario(Pagamento $pagamento): string
    {
        $beneficiario = $pagamento->payable;

        return $beneficiario?->nome ?? $beneficiario?->nome_exibicao ?? '—';
    }
}
