<?php

namespace App\Policies;

use App\Enums\StatusPagamento;
use App\Models\Pagamento;
use App\Models\User;
use App\Support\Permissoes;

class PagamentoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissoes::PAGAMENTOS_VER);
    }

    public function view(User $user, Pagamento $pagamento): bool
    {
        return $user->can(Permissoes::PAGAMENTOS_VER);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissoes::PAGAMENTOS_GERENCIAR);
    }

    public function update(User $user, Pagamento $pagamento): bool
    {
        return $user->can(Permissoes::PAGAMENTOS_GERENCIAR)
            && $pagamento->status !== StatusPagamento::Cancelado;
    }

    public function delete(User $user, Pagamento $pagamento): bool
    {
        return $user->can(Permissoes::PAGAMENTOS_GERENCIAR);
    }

    /**
     * Confirmar manualmente que o pagamento saiu (regra 3.6). Separado de
     * "gerenciar" porque quem lanca nao precisa ser quem confirma.
     */
    public function confirmar(User $user, Pagamento $pagamento): bool
    {
        return $user->can(Permissoes::PAGAMENTOS_CONFIRMAR)
            && $pagamento->status->podeIrPara(StatusPagamento::Pago);
    }

    public function alterarStatus(User $user, Pagamento $pagamento): bool
    {
        return $user->can(Permissoes::PAGAMENTOS_GERENCIAR)
            && $pagamento->status !== StatusPagamento::Cancelado;
    }

    public function anexar(User $user, Pagamento $pagamento): bool
    {
        return $user->can(Permissoes::PAGAMENTOS_GERENCIAR);
    }
}
