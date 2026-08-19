<?php

namespace App\Providers;

use App\Models\Colaborador;
use App\Models\ContaBancaria;
use App\Models\Contrato;
use App\Models\Fornecedor;
use App\Models\Pagamento;
use App\Models\Reembolso;
use App\Models\User;
use App\Support\Perfis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Nomes curtos nas colunas polimorficas: sem isso, o banco guarda
        // "App\Models\Colaborador" e um refactor de namespace quebra o historico.
        Relation::enforceMorphMap([
            'colaborador' => Colaborador::class,
            'fornecedor' => Fornecedor::class,
            'pagamento' => Pagamento::class,
            'reembolso' => Reembolso::class,
            'contrato' => Contrato::class,
            // Nao e alvo de relacao polimorfica, mas o activitylog grava
            // subject_type e o mapa e obrigatorio para todo modelo logado.
            'conta_bancaria' => ContaBancaria::class,
            // Usado pelo spatie/laravel-permission em model_has_roles.
            'user' => User::class,
        ]);

        // Administrador passa por cima das policies (regra 3.9).
        Gate::before(fn ($user) => $user->hasRole(Perfis::ADMINISTRADOR) ? true : null);

        // Um lancamento salvo pela metade por causa de um campo digitado errado
        // e pior que um erro na cara do dev.
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::preventAccessingMissingAttributes(! $this->app->isProduction());
    }
}
