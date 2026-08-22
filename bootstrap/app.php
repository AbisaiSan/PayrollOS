<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        /*
         * Telas proprias para 403 (tarefa 38).
         *
         * Sem isto o usuario cai na pagina crua do Laravel, fora da casca da
         * aplicacao e sem caminho de volta. So 403 e interceptado: 500 continua
         * mostrando o erro real, que e o que o desenvolvedor precisa ver.
         */
        $exceptions->respond(function (SymfonyResponse $resposta, Throwable $excecao, Request $request) {
            if ($resposta->getStatusCode() !== 403 || $request->expectsJson()) {
                return $resposta;
            }

            // Sem sessao nao ha casca autenticada para desenhar; o redirect do
            // middleware de auth resolve melhor esse caso.
            if (! $request->user()) {
                return $resposta;
            }

            return Inertia::render('Erros/403')
                ->toResponse($request)
                ->setStatusCode(403);
        });
    })->create();
