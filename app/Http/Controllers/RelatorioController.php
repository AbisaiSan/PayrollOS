<?php

namespace App\Http\Controllers;

use App\Enums\StatusPagamento;
use App\Exports\RelatorioPagamentosExport;
use App\Models\CategoriaPagamento;
use App\Services\RelatorioService;
use App\Support\Permissoes;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Fase 6 do plano. A consolidacao junta folha, fornecedores e reembolsos.
 *
 * Tela e arquivo exportado saem do mesmo RelatorioService de proposito: o modal
 * de exportacao promete manter os filtros aplicados na tela, e duas consultas
 * separadas divergiriam na primeira mudanca de regra em so uma delas.
 */
class RelatorioController extends Controller
{
    public function __construct(private readonly RelatorioService $service) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can(Permissoes::RELATORIOS_VER), 403);

        $filtros = $this->service->filtros($request);

        return Inertia::render('Relatorios/Index', [
            'filtros' => $filtros,
            'resumo' => $this->service->resumo($filtros),
            'porStatus' => $this->service->porStatus($filtros),
            'porCategoria' => $this->service->porCategoria($filtros),
            'opcoes' => [
                'categorias' => CategoriaPagamento::ativas()->orderBy('nome')->get(['id', 'nome']),
                'status' => StatusPagamento::opcoes(),
            ],
        ]);
    }

    public function exportar(Request $request): BinaryFileResponse|HttpResponse
    {
        abort_unless($request->user()->can(Permissoes::RELATORIOS_EXPORTAR), 403);

        $request->validate([
            'formato' => ['required', 'in:xlsx,pdf'],
            'inicio' => ['nullable', 'date'],
            'fim' => ['nullable', 'date', 'after_or_equal:inicio'],
            'categoria_id' => ['nullable', 'exists:categorias_pagamento,id'],
            'status' => ['nullable', 'string'],
        ], [
            'formato.in' => 'Formato inválido. Use xlsx ou pdf.',
        ]);

        $filtros = $this->service->filtros($request);
        $dados = $this->dadosDoRelatorio($request, $filtros);
        $arquivo = $this->nomeDoArquivo($filtros);

        if ($request->string('formato')->toString() === 'pdf') {
            return Pdf::loadView('relatorios.pdf', $dados)
                ->setPaper('a4')
                // Sem subsetting o dompdf embute a DejaVu inteira e um relatorio de
                // uma pagina sai com 860 KB. Com ele, so os glifos usados entram.
                ->setOption('enable_font_subsetting', true)
                ->download($arquivo.'.pdf');
        }

        return Excel::download(new RelatorioPagamentosExport($dados), $arquivo.'.xlsx');
    }

    /**
     * O mesmo conjunto alimenta a planilha e o PDF. Os agregados vem do service,
     * que e o que a tela tambem consome; o que se acrescenta aqui e so o contexto
     * de quem gerou e quando, que o arquivo precisa carregar e a tela nao.
     *
     * @param  array{inicio: string, fim: string, categoria_id: ?int, status: ?string}  $filtros
     * @return array<string, mixed>
     */
    private function dadosDoRelatorio(Request $request, array $filtros): array
    {
        $resumo = $this->service->resumo($filtros);

        $categoria = $filtros['categoria_id']
            ? CategoriaPagamento::find($filtros['categoria_id'])?->nome
            : null;

        $status = $filtros['status']
            ? StatusPagamento::tryFrom($filtros['status'])?->rotulo()
            : null;

        return [
            'periodo' => $this->formatarData($filtros['inicio']).' a '.$this->formatarData($filtros['fim']),
            'filtroCategoria' => $categoria,
            'filtroStatus' => $status,
            'geradoEm' => now()->format('d/m/Y H:i'),
            'geradoPor' => $request->user()->name,
            'resumo' => $resumo,
            'ticketMedio' => $resumo['quantidade'] > 0
                ? $resumo['total'] / $resumo['quantidade']
                : 0.0,
            'porStatus' => $this->service->porStatus($filtros),
            'porCategoria' => $this->service->porCategoria($filtros),
            'lancamentos' => $this->service->lancamentos($filtros),
            'marca' => config('payrollos.marca'),
            // O Blade do PDF formata moeda; passar a funcao evita repetir a
            // configuracao de locale em cada linha da view.
            'moeda' => fn (float $valor) => 'R$ '.number_format($valor, 2, ',', '.'),
        ];
    }

    /**
     * @param  array{inicio: string, fim: string, categoria_id: ?int, status: ?string}  $filtros
     */
    private function nomeDoArquivo(array $filtros): string
    {
        return 'relatorio-pagamentos-'.$filtros['inicio'].'-a-'.$filtros['fim'];
    }

    private function formatarData(string $data): string
    {
        return Carbon::parse($data)->format('d/m/Y');
    }
}
