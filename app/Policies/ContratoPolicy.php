<?php

namespace App\Policies;

use App\Models\Contrato;
use App\Models\User;
use App\Support\Permissoes;

class ContratoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissoes::CONTRATOS_VER);
    }

    public function view(User $user, Contrato $contrato): bool
    {
        return $user->can(Permissoes::CONTRATOS_VER);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissoes::CONTRATOS_GERENCIAR);
    }

    public function update(User $user, Contrato $contrato): bool
    {
        return $user->can(Permissoes::CONTRATOS_GERENCIAR);
    }

    public function delete(User $user, Contrato $contrato): bool
    {
        return $user->can(Permissoes::CONTRATOS_GERENCIAR);
    }
}
