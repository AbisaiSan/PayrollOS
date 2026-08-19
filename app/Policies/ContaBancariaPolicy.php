<?php

namespace App\Policies;

use App\Models\ContaBancaria;
use App\Models\User;
use App\Support\Permissoes;

class ContaBancariaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissoes::CONTAS_VER);
    }

    public function view(User $user, ContaBancaria $conta): bool
    {
        return $user->can(Permissoes::CONTAS_VER);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissoes::CONTAS_GERENCIAR);
    }

    public function update(User $user, ContaBancaria $conta): bool
    {
        return $user->can(Permissoes::CONTAS_GERENCIAR);
    }

    /**
     * Contas nao sao excluidas, apenas inativadas (regra 3.2).
     */
    public function inativar(User $user, ContaBancaria $conta): bool
    {
        return $user->can(Permissoes::CONTAS_GERENCIAR);
    }
}
