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
         * Telas proprias para 403 e 404 (tarefas 38 e 39).
         *
         * Sem isto o usuario cai na pagina crua do Laravel, fora da casca da
         * aplicacao e sem caminho de volta. So estes dois sao interceptados: 500
         * continua mostrando o erro real, que e o que o desenvolvedor precisa ver.
         */
        $exceptions->respond(function (SymfonyResponse $resposta, Throwable $excecao, Request $request) {
            $status = $resposta->getStatusCode();

            if (! in_array($status, [403, 404], true) || $request->expectsJson()) {
                return $resposta;
            }

            // Sem sessao nao ha casca autenticada para desenhar; o redirect do
            // middleware de auth resolve melhor esse caso.
            if (! $request->user()) {
                return $resposta;
            }

            return Inertia::render("Erros/{$status}")
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
