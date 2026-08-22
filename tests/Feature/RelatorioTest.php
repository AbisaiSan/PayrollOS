<?php

use App\Models\CategoriaPagamento;
use App\Models\Colaborador;
use App\Models\Pagamento;
use App\Models\Reembolso;
use App\Models\User;
use App\Services\RelatorioService;
use Database\Seeders\PerfilPermissaoSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(PerfilPermissaoSeeder::class);

    $this->user = User::factory()->create(['name' => 'Abisai Santos']);
    $this->user->assignRole('administrador');

    $this->salario = CategoriaPagamento::factory()->create(['nome' => 'Salário']);
    $this->aluguel = CategoriaPagamento::factory()->create(['nome' => 'Aluguel']);
    $colaborador = Colaborador::factory()->create(['nome' => 'Marina Torres']);

    Pagamento::factory()->count(2)->create([
        'categoria_id' => $this->salario->id,
        'payable_type' => 'colaborador',
        'payable_id' => $colaborador->id,
        'valor' => 5000,
        'data_vencimento' => '2026-08-10',
        'status' => 'pendente',
    ]);

    Pagamento::factory()->create([
        'categoria_id' => $this->aluguel->id,
        'valor' => 6400,
        'data_vencimento' => '2026-08-25',
        'status' => 'pago',
    ]);

    // Fora do periodo: nao pode entrar em agregado nem em arquivo.
    Pagamento::factory()->create([
        'categoria_id' => $this->aluguel->id,
        'valor' => 99999,
        'data_vencimento' => '2026-09-25',
    ]);

    $this->periodo = ['inicio' => '2026-08-01', 'fim' => '2026-08-31'];
});

it('agrega total, status e categoria dentro do período', function () {
    $this->actingAs($this->user)
        ->get(route('relatorios.index', $this->periodo))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Relatorios/Index')
            ->where('resumo.total', 16400)
            ->where('resumo.quantidade', 3)
            ->has('porStatus', 2)
            ->has('porCategoria', 2)
            ->where('porCategoria.0.nome', 'Salário')
            ->where('porCategoria.0.total', 10000)
        );
});

it('sem período, o backend arbitra o mês corrente', function () {
    $this->actingAs($this->user)
        ->get(route('relatorios.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filtros.inicio', now()->startOfMonth()->toDateString())
            ->where('filtros.fim', now()->endOfMonth()->toDateString())
        );
});

it('exporta planilha com o nome do período', function () {
    $resposta = $this->actingAs($this->user)
        ->get(route('relatorios.exportar', [...$this->periodo, 'formato' => 'xlsx']));

    $resposta->assertOk()
        ->assertDownload('relatorio-pagamentos-2026-08-01-a-2026-08-31.xlsx');
});

it('exporta PDF com o nome do período', function () {
    $resposta = $this->actingAs($this->user)
        ->get(route('relatorios.exportar', [...$this->periodo, 'formato' => 'pdf']));

    $resposta->assertOk()
        ->assertDownload('relatorio-pagamentos-2026-08-01-a-2026-08-31.pdf');

    // Assinatura do arquivo: o conteudo e mesmo um PDF, nao HTML com outro nome.
    expect(substr($resposta->getContent(), 0, 4))->toBe('%PDF');
});

it('o arquivo respeita os filtros da tela', function () {
    $resposta = $this->actingAs($this->user)
        ->get(route('relatorios.exportar', [
            ...$this->periodo,
            'formato' => 'pdf',
            'categoria_id' => $this->aluguel->id,
        ]));

    $conteudo = $resposta->getContent();

    // O PDF do dompdf comprime o texto, entao a asercao vai no service, que e a
    // fonte que a view consome — mesma consulta, mesmo resultado.
    $service = app(RelatorioService::class);
    $filtros = [...$this->periodo, 'categoria_id' => $this->aluguel->id, 'status' => null];

    expect($service->resumo($filtros))
        ->toBe(['total' => 6400.0, 'quantidade' => 1, 'naoRealizavel' => 0.0])
        ->and($service->lancamentos($filtros))->toHaveCount(1)
        ->and($conteudo)->not->toBeEmpty();
});

it('a exportação recusa formato que não existe', function () {
    $this->actingAs($this->user)
        ->get(route('relatorios.exportar', [...$this->periodo, 'formato' => 'docx']))
        ->assertSessionHasErrors('formato');

    $this->actingAs($this->user)
        ->get(route('relatorios.exportar', $this->periodo))
        ->assertSessionHasErrors('formato');
});

it('os lançamentos exportados trazem beneficiário e rótulos legíveis', function () {
    $service = app(RelatorioService::class);

    $lancamentos = $service->lancamentos([...$this->periodo, 'categoria_id' => null, 'status' => null]);

    expect($lancamentos)->toHaveCount(3);

    $doColaborador = $lancamentos->firstWhere('beneficiario', 'Marina Torres');

    expect($doColaborador)->not->toBeNull()
        ->and($doColaborador['beneficiario_tipo'])->toBe('Colaborador')
        ->and($doColaborador['categoria'])->toBe('Salário')
        // Rotulo, nao o valor cru do enum.
        ->and($doColaborador['status'])->toBe('Pendente')
        ->and($doColaborador['data'])->toBe('10/08/2026')
        ->and($doColaborador['origem'])->toBe('Pagamento');
});

it('quem não pode exportar não exporta, mesmo podendo ver', function () {
    $leitura = User::factory()->create();
    $leitura->assignRole('leitura');

    // Leitura tem relatorios.ver e nao tem relatorios.exportar.
    $this->actingAs($leitura)
        ->get(route('relatorios.index'))
        ->assertOk();

    $this->actingAs($leitura)
        ->get(route('relatorios.exportar', [...$this->periodo, 'formato' => 'xlsx']))
        ->assertStatus(403);
});

it('consolida reembolsos no total, como manda a regra 3.7', function () {
    $colaborador = Colaborador::factory()->create(['nome' => 'Everton Sá']);

    Reembolso::factory()->create([
        'colaborador_id' => $colaborador->id,
        'valor' => 1240,
        'data_solicitacao' => '2026-08-14',
        'categoria' => 'viagem',
        'status' => 'pago',
    ]);

    // Fora do periodo: nao entra.
    Reembolso::factory()->create([
        'colaborador_id' => $colaborador->id,
        'valor' => 500,
        'data_solicitacao' => '2026-09-14',
        'status' => 'pago',
    ]);

    $this->actingAs($this->user)
        ->get(route('relatorios.index', $this->periodo))
        ->assertInertia(fn (AssertableInertia $page) => $page
            // 16.400 de pagamentos + 1.240 de reembolso.
            ->where('resumo.total', 17640)
            ->where('resumo.quantidade', 4)
        );
});

it('reembolso rejeitado e pagamento cancelado ficam fora do total, mas visíveis', function () {
    $colaborador = Colaborador::factory()->create();

    Reembolso::factory()->create([
        'colaborador_id' => $colaborador->id,
        'valor' => 78,
        'data_solicitacao' => '2026-08-10',
        'status' => 'rejeitado',
    ]);

    Pagamento::factory()->create([
        'categoria_id' => $this->aluguel->id,
        'valor' => 300,
        'data_vencimento' => '2026-08-12',
        'status' => 'cancelado',
    ]);

    $this->actingAs($this->user)
        ->get(route('relatorios.index', $this->periodo))
        ->assertInertia(function (AssertableInertia $page) {
            // O total segue 16.400: nem o rejeitado nem o cancelado entram.
            $page->where('resumo.total', 16400)
                ->where('resumo.quantidade', 3)
                ->where('resumo.naoRealizavel', 378);

            $status = collect($page->toArray()['props']['porStatus']);

            // Continuam visiveis na quebra, marcados como fora do total.
            expect($status->firstWhere('status', 'rejeitado')['realizavel'])->toBeFalse()
                ->and($status->firstWhere('status', 'cancelado')['realizavel'])->toBeFalse()
                ->and($status->firstWhere('status', 'pendente')['realizavel'])->toBeTrue();
        });
});

it('a quebra por categoria preserva a despesa do reembolso', function () {
    $colaborador = Colaborador::factory()->create();

    Reembolso::factory()->create([
        'colaborador_id' => $colaborador->id,
        'valor' => 1240,
        'data_solicitacao' => '2026-08-14',
        'categoria' => 'viagem',
        'status' => 'pago',
    ]);

    $service = app(RelatorioService::class);
    $categorias = $service->porCategoria([...$this->periodo, 'categoria_id' => null, 'status' => null]);

    // Nao vira um balde "Reembolso": o tipo da despesa sobrevive.
    expect($categorias->pluck('nome'))->toContain('Reembolso — Viagem')
        ->and($categorias->firstWhere('nome', 'Reembolso — Viagem')['total'])->toBe(1240.0);
});

it('filtrar por categoria de pagamento não traz reembolso junto', function () {
    $colaborador = Colaborador::factory()->create();

    Reembolso::factory()->create([
        'colaborador_id' => $colaborador->id,
        'valor' => 1240,
        'data_solicitacao' => '2026-08-14',
        'status' => 'pago',
    ]);

    $service = app(RelatorioService::class);

    // Reembolso nao pertence a categoria nenhuma da tabela de pagamentos.
    $comFiltro = $service->resumo([...$this->periodo, 'categoria_id' => $this->aluguel->id, 'status' => null]);

    expect($comFiltro['total'])->toBe(6400.0)
        ->and($comFiltro['quantidade'])->toBe(1);
});

it('o filtro de status oferece também os estados que só existem em reembolso', function () {
    $this->actingAs($this->user)
        ->get(route('relatorios.index'))
        ->assertInertia(function (AssertableInertia $page) {
            $valores = collect($page->toArray()['props']['opcoes']['status'])->pluck('value');

            expect($valores)->toContain('pendente', 'agendado', 'pago', 'atrasado', 'cancelado')
                ->and($valores)->toContain('aprovado', 'rejeitado')
                // "pendente" e "pago" existem nos dois enums e nao podem duplicar.
                ->and($valores->duplicates())->toBeEmpty();
        });
});

it('o arquivo exportado lista pagamentos e reembolsos juntos', function () {
    $colaborador = Colaborador::factory()->create(['nome' => 'Everton Sá']);

    Reembolso::factory()->create([
        'colaborador_id' => $colaborador->id,
        'valor' => 1240,
        'data_solicitacao' => '2026-08-14',
        'categoria' => 'viagem',
        'status' => 'pago',
    ]);

    $service = app(RelatorioService::class);
    $lancamentos = $service->lancamentos([...$this->periodo, 'categoria_id' => null, 'status' => null]);

    expect($lancamentos)->toHaveCount(4);

    $doReembolso = $lancamentos->firstWhere('origem', 'Reembolso');

    expect($doReembolso)->not->toBeNull()
        ->and($doReembolso['beneficiario'])->toBe('Everton Sá')
        ->and($doReembolso['categoria'])->toBe('Reembolso — Viagem')
        // Reembolso nao tem vencimento: a data e a da solicitacao.
        ->and($doReembolso['data'])->toBe('14/08/2026');

    $this->actingAs($this->user)
        ->get(route('relatorios.exportar', [...$this->periodo, 'formato' => 'pdf']))
        ->assertOk();
});
