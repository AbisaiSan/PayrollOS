{{--
    Planilha do relatorio consolidado.

    Sem estilo: o Excel ignora quase tudo, e o que importa aqui e a estrutura das
    celulas. O cabecalho de contexto vem antes da tabela de propositio, para o
    arquivo dizer de que recorte ele saiu.
--}}
<table>
    <tr><td><strong>Relatório de pagamentos — PayrollOS</strong></td></tr>
    <tr><td>Período</td><td>{{ $periodo }}</td></tr>
    <tr><td>Categoria</td><td>{{ $filtroCategoria ?? 'Todas' }}</td></tr>
    <tr><td>Status</td><td>{{ $filtroStatus ?? 'Todos' }}</td></tr>
    <tr><td>Gerado em</td><td>{{ $geradoEm }}</td></tr>
    <tr><td>Gerado por</td><td>{{ $geradoPor }}</td></tr>
    <tr></tr>

    <tr><td><strong>Resumo</strong></td></tr>
    <tr><td>Total no período</td><td>{{ $resumo['total'] }}</td></tr>
    <tr><td>Lançamentos</td><td>{{ $resumo['quantidade'] }}</td></tr>
    <tr><td>Ticket médio</td><td>{{ $ticketMedio }}</td></tr>
    <tr></tr>

    <tr><td><strong>Por status</strong></td></tr>
    <tr><td>Status</td><td>Quantidade</td><td>Total</td></tr>
    @foreach ($porStatus as $linha)
        <tr>
            <td>{{ $linha['rotulo'] }}</td>
            <td>{{ $linha['quantidade'] }}</td>
            <td>{{ $linha['total'] }}</td>
        </tr>
    @endforeach
    <tr></tr>

    <tr><td><strong>Por categoria</strong></td></tr>
    <tr><td>Categoria</td><td>Total</td></tr>
    @foreach ($porCategoria as $linha)
        <tr>
            <td>{{ $linha['nome'] }}</td>
            <td>{{ $linha['total'] }}</td>
        </tr>
    @endforeach
    <tr></tr>

    <tr><td><strong>Lançamentos</strong></td></tr>
    <tr>
        <td>ID</td>
        <td>Descrição</td>
        <td>Beneficiário</td>
        <td>Tipo</td>
        <td>Categoria</td>
        <td>Competência</td>
        <td>Vencimento</td>
        <td>Pagamento</td>
        <td>Forma</td>
        <td>Status</td>
        <td>Valor</td>
    </tr>
    @forelse ($lancamentos as $linha)
        <tr>
            <td>{{ $linha['id'] }}</td>
            <td>{{ $linha['descricao'] }}</td>
            <td>{{ $linha['beneficiario'] }}</td>
            <td>{{ $linha['beneficiario_tipo'] }}</td>
            <td>{{ $linha['categoria'] }}</td>
            <td>{{ $linha['competencia'] ?? '' }}</td>
            <td>{{ $linha['data_vencimento'] }}</td>
            <td>{{ $linha['data_pagamento'] ?? '' }}</td>
            <td>{{ $linha['forma_pagamento'] }}</td>
            <td>{{ $linha['status'] }}</td>
            <td>{{ $linha['valor'] }}</td>
        </tr>
    @empty
        <tr><td colspan="11">Nenhum lançamento no período escolhido.</td></tr>
    @endforelse
</table>
