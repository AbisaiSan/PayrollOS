<?php

namespace App\Policies;

use App\Models\CategoriaPagamento;
use App\Models\User;
use App\Support\Permissoes;

class CategoriaPagamentoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissoes::CATEGORIAS_VER);
    }

    public function view(User $user, CategoriaPagamento $categoria): bool
    {
        return $user->can(Permissoes::CATEGORIAS_VER);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissoes::CATEGORIAS_GERENCIAR);
    }

    public function update(User $user, CategoriaPagamento $categoria): bool
    {
        return $user->can(Permissoes::CATEGORIAS_GERENCIAR);
    }

    /**
     * Categoria com pagamento vinculado nao pode sumir: quebraria o relatorio
     * historico. Nesse caso o caminho e desativar.
     */
    public function delete(User $user, CategoriaPagamento $categoria): bool
    {
        return $user->can(Permissoes::CATEGORIAS_GERENCIAR)
            && ! $categoria->pagamentos()->exists();
    }
}
