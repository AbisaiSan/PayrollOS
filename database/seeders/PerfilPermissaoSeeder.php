<?php

namespace Database\Seeders;

use App\Support\Perfis;
use App\Support\Permissoes;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PerfilPermissaoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Permissoes::todas() as $permissao) {
            Permission::findOrCreate($permissao, 'web');
        }

        // Limpar depois de criar: o syncPermissions abaixo resolve os nomes pelo
        // cache, que ainda estaria vazio se so limpassemos no inicio.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Perfis::permissoesPorPerfil() as $perfil => $permissoes) {
            $role = Role::findOrCreate($perfil, 'web');
            // syncPermissions e nao givePermissionTo: rodar o seeder de novo deve
            // refletir permissoes removidas do catalogo, nao so as adicionadas.
            $role->syncPermissions($permissoes);
        }
    }
}
