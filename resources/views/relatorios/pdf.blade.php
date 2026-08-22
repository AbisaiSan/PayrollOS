<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório de pagamentos</title>
    {{--
        DejaVu Sans e a fonte que o dompdf embute por padrao e a unica com
        acentuacao garantida sem empacotar arquivo de fonte no repositorio.
        Cor e medida vem de config('payrollos.marca'), que espelha o
        tailwind.config para o backend, que nao passa pelo Tailwind.
    --}}
    <style>
        @page { margin: 28px 32px 48px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color: #0D0E0E; }
        h1 { font-size: 15px; margin: 0 0 2px; }
        .marca { color: {{ $marca['laranja'] }}; font-weight: bold; }
        .sub { color: #666; font-size: 8.5px; margin: 0 0 14px; }
        .contexto { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .contexto td { padding: 2px 0; font-size: 8.5px; }
        .contexto td:first-child { color: #666; width: 90px; }
        h2 { font-size: 10px; text-transform: uppercase; letter-spacing: .5px;
             color: #666; margin: 16px 0 6px; border-bottom: 1px solid #DDD;
             padding-bottom: 3px; }
        table.grid { width: 100%; border-collapse: collapse; }
        table.grid th { background: #F4F4F3; text-align: left; font-size: 8px;
                        text-transform: uppercase; letter-spacing: .4px; color: #555;
                        padding: 5px 6px; border-bottom: 1px solid #DDD; }
        table.grid td { padding: 4px 6px; border-bottom: 1px solid #EEE; }
        table.grid tr:nth-child(even) td { background: #FAFAFA; }
        .num { text-align: right; white-space: nowrap; }
        .cards { width: 100%; border-collapse: separate; border-spacing: 8px 0; }
        .card { border: 1px solid #DDD; border-radius: 5px; padding: 8px 10px; width: 33%; }
        .card .rot { color: #666; font-size: 8px; text-transform: uppercase;
                     letter-spacing: .4px; }
        .card .val { font-size: 13px; font-weight: bold; padding-top: 3px; }
        .rodape { position: fixed; bottom: -28px; left: 0; right: 0; color: #888;
                  font-size: 7.5px; border-top: 1px solid #EEE; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="rodape">
        PayrollOS · Corebanx — sistema de controle interno, não executa pagamentos.
        Gerado em {{ $geradoEm }} por {{ $geradoPor }}.
    </div>

    <h1><span class="marca">PayrollOS</span> — Relatório de pagamentos</h1>
    <p class="sub">Consolidado por status e categoria</p>

    <table class="contexto">
        <tr><td>Período</td><td><strong>{{ $periodo }}</strong></td></tr>
        <tr><td>Categoria</td><td>{{ $filtroCategoria ?? 'Todas' }}</td></tr>
        <tr><td>Status</td><td>{{ $filtroStatus ?? 'Todos' }}</td></tr>
    </table>

    <table class="cards">
        <tr>
            <td class="card">
                <div class="rot">Total no período</div>
                <div class="val">{{ $moeda($resumo['total']) }}</div>
            </td>
            <td class="card">
                <div class="rot">Lançamentos</div>
                <div class="val">{{ $resumo['quantidade'] }}</div>
            </td>
            <td class="card">
                <div class="rot">Ticket médio</div>
                <div class="val">{{ $moeda($ticketMedio) }}</div>
            </td>
        </tr>
    </table>

    <h2>Por status</h2>
    <table class="grid">
        <thead>
            <tr><th>Status</th><th class="num">Quantidade</th><th class="num">Total</th></tr>
        </thead>
        <tbody>
            @forelse ($porStatus as $linha)
                <tr>
                    <td>{{ $linha['rotulo'] }}</td>
                    <td class="num">{{ $linha['quantidade'] }}</td>
                    <td class="num">{{ $moeda($linha['total']) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Nenhum lançamento no período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Por categoria</h2>
    <table class="grid">
        <thead><tr><th>Categoria</th><th class="num">Total</th></tr></thead>
        <tbody>
            @forelse ($porCategoria as $linha)
                <tr>
                    <td>{{ $linha['nome'] }}</td>
                    <td class="num">{{ $moeda($linha['total']) }}</td>
                </tr>
            @empty
                <tr><td colspan="2">Nenhum lançamento no período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Lançamentos</h2>
    <table class="grid">
        <thead>
            <tr>
                <th>Descrição</th>
                <th>Beneficiário</th>
                <th>Categoria</th>
                <th>Vencimento</th>
                <th>Status</th>
                <th class="num">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($lancamentos as $linha)
                <tr>
                    <td>{{ $linha['descricao'] }}</td>
                    <td>{{ $linha['beneficiario'] }}</td>
                    <td>{{ $linha['categoria'] }}</td>
                    <td class="num">{{ $linha['data_vencimento'] }}</td>
                    <td>{{ $linha['status'] }}</td>
                    <td class="num">{{ $moeda($linha['valor']) }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Nenhum lançamento no período escolhido.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
