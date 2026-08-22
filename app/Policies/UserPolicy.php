<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissoes;

/**
 * Gestao de contas de acesso (regra 3.9).
 *
 * Nao confundir com ColaboradorPolicy: aqui se trata de quem entra no sistema,
 * la de quem entra na folha.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissoes::USUARIOS_GERENCIAR);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissoes::USUARIOS_GERENCIAR);
    }

    public function update(User $user, User $alvo): bool
    {
        return $user->can(Permissoes::USUARIOS_GERENCIAR);
    }

    /**
     * Desativar a propria conta tiraria o acesso de quem esta no meio da acao, e
     * se fosse o unico administrador ninguem mais conseguiria reativar ninguem.
     */
    public function alternarAtivo(User $user, User $alvo): bool
    {
        return $user->can(Permissoes::USUARIOS_GERENCIAR)
            && $user->id !== $alvo->id;
    }
}
