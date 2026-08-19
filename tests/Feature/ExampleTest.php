<?php

use App\Models\User;
use App\Support\Perfis;
use Database\Seeders\PerfilPermissaoSeeder;

it('redireciona a raiz para o dashboard, já que não há área pública', function () {
    $this->get('/')->assertRedirect('/dashboard');
});

it('exige autenticação para acessar o dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('entrega o dashboard para um usuário autenticado', function () {
    $this->seed(PerfilPermissaoSeeder::class);

    $usuario = User::factory()->create();
    $usuario->assignRole(Perfis::FINANCEIRO);

    $this->actingAs($usuario)
        ->get('/dashboard')
        ->assertOk();
});
