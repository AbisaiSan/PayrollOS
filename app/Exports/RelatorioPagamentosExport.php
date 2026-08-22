<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

/**
 * Planilha do relatorio consolidado.
 *
 * FromView e nao FromCollection: a planilha carrega o cabecalho com periodo e
 * filtros aplicados antes da tabela, e uma colecao pura so produziria linhas
 * soltas. Quem abre o arquivo tres meses depois precisa saber de que recorte ele
 * veio, senao o numero nao significa nada.
 */
class RelatorioPagamentosExport implements FromView, ShouldAutoSize
{
    /**
     * @param  array<string, mixed>  $dados
     */
    public function __construct(private readonly array $dados) {}

    public function view(): View
    {
        return view('relatorios.planilha', $this->dados);
    }
}
