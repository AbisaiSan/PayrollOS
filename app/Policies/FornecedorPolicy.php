<?php

namespace App\Policies;

use App\Models\Fornecedor;
use App\Models\User;
use App\Support\Permissoes;

class FornecedorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissoes::FORNECEDORES_VER);
    }

    public function view(User $user, Fornecedor $fornecedor): bool
    {
        return $user->can(Permissoes::FORNECEDORES_VER);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissoes::FORNECEDORES_GERENCIAR);
    }

    public function update(User $user, Fornecedor $fornecedor): bool
    {
        return $user->can(Permissoes::FORNECEDORES_GERENCIAR);
    }

    public function delete(User $user, Fornecedor $fornecedor): bool
    {
        return $user->can(Permissoes::FORNECEDORES_GERENCIAR);
    }
}
