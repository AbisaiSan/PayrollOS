<?php

namespace App\Policies;

use App\Models\Colaborador;
use App\Models\User;
use App\Support\Permissoes;

class ColaboradorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissoes::COLABORADORES_VER);
    }

    public function view(User $user, Colaborador $colaborador): bool
    {
        return $user->can(Permissoes::COLABORADORES_VER);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissoes::COLABORADORES_GERENCIAR);
    }

    public function update(User $user, Colaborador $colaborador): bool
    {
        return $user->can(Permissoes::COLABORADORES_GERENCIAR);
    }

    /**
     * Colaborador nunca e apagado de fato: o historico de pagamentos precisa
     * continuar rastreavel (regra 3.1). "Excluir" aqui e soft delete.
     */
    public function delete(User $user, Colaborador $colaborador): bool
    {
        return $user->can(Permissoes::COLABORADORES_GERENCIAR);
    }

    public function desligar(User $user, Colaborador $colaborador): bool
    {
        return $user->can(Permissoes::COLABORADORES_GERENCIAR);
    }
}
