<?php

namespace App\Policies;

use App\Enums\StatusReembolso;
use App\Models\Reembolso;
use App\Models\User;
use App\Support\Permissoes;

class ReembolsoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissoes::REEMBOLSOS_VER);
    }

    /**
     * Gestor so tem REEMBOLSOS_VER, entao enxerga tudo que a listagem trouxer.
     * O recorte por equipe entra aqui quando a hierarquia de gestor existir
     * (hoje o plano nao define quem e gestor de quem).
     */
    public function view(User $user, Reembolso $reembolso): bool
    {
        return $user->can(Permissoes::REEMBOLSOS_VER);
    }

    public function create(User $user): bool
    {
        return $user->canAny([Permissoes::REEMBOLSOS_GERENCIAR, Permissoes::REEMBOLSOS_SOLICITAR]);
    }

    public function update(User $user, Reembolso $reembolso): bool
    {
        if ($reembolso->status === StatusReembolso::Pago) {
            return false;
        }

        if ($user->can(Permissoes::REEMBOLSOS_GERENCIAR)) {
            return true;
        }

        // Quem so pode solicitar edita a propria solicitacao enquanto pendente.
        return $user->can(Permissoes::REEMBOLSOS_SOLICITAR)
            && $reembolso->solicitado_por === $user->id
            && $reembolso->status === StatusReembolso::Pendente;
    }

    public function delete(User $user, Reembolso $reembolso): bool
    {
        return $user->can(Permissoes::REEMBOLSOS_GERENCIAR)
            && $reembolso->status !== StatusReembolso::Pago;
    }

    public function aprovar(User $user, Reembolso $reembolso): bool
    {
        return $user->can(Permissoes::REEMBOLSOS_APROVAR)
            && $reembolso->status->podeIrPara(StatusReembolso::Aprovado);
    }

    public function confirmarPagamento(User $user, Reembolso $reembolso): bool
    {
        return $user->can(Permissoes::REEMBOLSOS_GERENCIAR)
            && $reembolso->status->podeIrPara(StatusReembolso::Pago);
    }
}
