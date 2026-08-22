<?php

use App\Models\Colaborador;
use App\Models\User;
use App\Support\Navegacao;
use Database\Seeders\PerfilPermissaoSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(PerfilPermissaoSeeder::class);

    $this->admin = User::factory()->create(['name' => 'Abisai Santos']);
    $this->admin->assignRole('administrador');
});

$payload = fn (array $extra = []) => [
    'name' => 'Marina Torres',
    'email' => 'marina.torres@corebanx.com',
    'password' => 'senha-bem-longa-2026',
    'password_confirmation' => 'senha-bem-longa-2026',
    'perfil' => 'financeiro',
    'colaborador_id' => null,
    'ativo' => true,
    ...$extra,
];

it('cria a conta com o perfil escolhido', function () use ($payload) {
    $this->actingAs($this->admin)
        ->post(route('usuarios.store'), $payload())
        ->assertRedirect(route('usuarios.index'));

    $usuario = User::where('email', 'marina.torres@corebanx.com')->sole();

    expect($usuario->hasRole('financeiro'))->toBeTrue()
        ->and($usuario->ativo)->toBeTrue()
        ->and($usuario->password)->not->toBe('senha-bem-longa-2026');
});

it('troca o perfil sem acumular o anterior', function () use ($payload) {
    $this->actingAs($this->admin)->post(route('usuarios.store'), $payload());

    $usuario = User::where('email', 'marina.torres@corebanx.com')->sole();

    $this->actingAs($this->admin)
        ->put(route('usuarios.update', $usuario), $payload([
            'perfil' => 'leitura',
            'password' => '',
            'password_confirmation' => '',
        ]))
        ->assertRedirect();

    $usuario->refresh();

    expect($usuario->hasRole('leitura'))->toBeTrue()
        ->and($usuario->hasRole('financeiro'))->toBeFalse()
        ->and($usuario->roles)->toHaveCount(1);
});

it('mantém a senha atual quando o campo vem em branco', function () use ($payload) {
    $this->actingAs($this->admin)->post(route('usuarios.store'), $payload());

    $usuario = User::where('email', 'marina.torres@corebanx.com')->sole();
    $hashAntes = $usuario->password;

    $this->actingAs($this->admin)
        ->put(route('usuarios.update', $usuario), $payload([
            'name' => 'Marina Torres Albuquerque',
            'password' => '',
            'password_confirmation' => '',
        ]))
        ->assertSessionHasNoErrors();

    $usuario->refresh();

    expect($usuario->name)->toBe('Marina Torres Albuquerque')
        ->and($usuario->password)->toBe($hashAntes);
});

it('desativar a conta impede o login', function () use ($payload) {
    $this->actingAs($this->admin)->post(route('usuarios.store'), $payload());

    $usuario = User::where('email', 'marina.torres@corebanx.com')->sole();

    $this->actingAs($this->admin)
        ->post(route('usuarios.ativo', $usuario))
        ->assertSessionHasNoErrors();

    expect($usuario->refresh()->ativo)->toBeFalse();

    auth()->logout();

    // Credencial certa, conta desativada: nao entra.
    $this->post(route('login'), [
        'email' => 'marina.torres@corebanx.com',
        'password' => 'senha-bem-longa-2026',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('reativar a conta devolve o login', function () use ($payload) {
    $this->actingAs($this->admin)->post(route('usuarios.store'), $payload(['ativo' => false]));

    $usuario = User::where('email', 'marina.torres@corebanx.com')->sole();

    $this->actingAs($this->admin)->post(route('usuarios.ativo', $usuario));

    expect($usuario->refresh()->ativo)->toBeTrue();

    auth()->logout();

    $this->post(route('login'), [
        'email' => 'marina.torres@corebanx.com',
        'password' => 'senha-bem-longa-2026',
    ])->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($usuario);
});

it('ninguém desativa a própria conta', function () {
    $this->actingAs($this->admin)
        ->post(route('usuarios.ativo', $this->admin))
        ->assertForbidden();

    expect($this->admin->refresh()->ativo)->toBeTrue();
});

it('um colaborador não responde por duas contas', function () use ($payload) {
    $colaborador = Colaborador::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('usuarios.store'), $payload(['colaborador_id' => $colaborador->id]))
        ->assertSessionHasNoErrors();

    $this->actingAs($this->admin)
        ->post(route('usuarios.store'), $payload([
            'email' => 'outra.pessoa@corebanx.com',
            'colaborador_id' => $colaborador->id,
        ]))
        ->assertSessionHasErrors('colaborador_id');
});

it('o formulário só oferece colaboradores ainda sem conta', function () use ($payload) {
    $comConta = Colaborador::factory()->create(['nome' => 'Já tem conta']);
    Colaborador::factory()->create(['nome' => 'Ainda não tem']);

    $this->actingAs($this->admin)
        ->post(route('usuarios.store'), $payload(['colaborador_id' => $comConta->id]));

    $this->actingAs($this->admin)
        ->get(route('usuarios.create'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Usuarios/Form')
            ->has('opcoes.colaboradores', 1)
            ->where('opcoes.colaboradores.0.nome', 'Ainda não tem')
            ->has('opcoes.perfis', 4)
        );
});

it('ao editar, o colaborador já vinculado continua na lista', function () use ($payload) {
    $colaborador = Colaborador::factory()->create(['nome' => 'Vinculada']);

    $this->actingAs($this->admin)
        ->post(route('usuarios.store'), $payload(['colaborador_id' => $colaborador->id]));

    $usuario = User::where('email', 'marina.torres@corebanx.com')->sole();

    $this->actingAs($this->admin)
        ->get(route('usuarios.edit', $usuario))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('opcoes.colaboradores', 1)
            ->where('opcoes.colaboradores.0.nome', 'Vinculada')
            ->where('usuario.perfil', 'financeiro')
        );
});

it('quem não tem usuarios.gerenciar não chega na tela', function () {
    $financeiro = User::factory()->create();
    $financeiro->assignRole('financeiro');

    $this->actingAs($financeiro)
        ->get(route('usuarios.index'))
        ->assertStatus(403);
});

it('o menu só mostra Usuários para quem pode gerenciar', function () {
    $financeiro = User::factory()->create();
    $financeiro->assignRole('financeiro');

    $grupos = Navegacao::paraUsuario($this->admin);
    $titulos = collect($grupos)->pluck('titulo');

    expect($titulos)->toContain('Sistema');

    $grupos = Navegacao::paraUsuario($financeiro);
    $titulos = collect($grupos)->pluck('titulo');

    expect($titulos)->not->toContain('Sistema');
});
