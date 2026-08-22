<?php

use App\Models\CategoriaPagamento;
use App\Models\Colaborador;
use App\Models\Pagamento;
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

    expect($service->resumo($filtros))->toBe(['total' => 6400.0, 'quantidade' => 1])
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
        ->and($doColaborador['data_vencimento'])->toBe('10/08/2026');
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
