<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Perfis;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PerfilPermissaoSeeder::class,
            CategoriaPagamentoSeeder::class,
        ]);

        // Usuario administrador inicial. Em producao, troque a senha no primeiro acesso.
        $admin = User::updateOrCreate(
            ['email' => env('PAYROLLOS_ADMIN_EMAIL', 'admin@corebanx.com.br')],
            [
                'name' => 'Administrador',
                'password' => Hash::make(env('PAYROLLOS_ADMIN_PASSWORD', 'password')),
                'email_verified_at' => now(),
                'ativo' => true,
            ],
        );

        $admin->syncRoles([Perfis::ADMINISTRADOR]);
    }
}
