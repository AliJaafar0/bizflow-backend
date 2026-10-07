<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
    'role' => \App\Http\Middleware\RoleMiddleware::class,
    'active' => \App\Http\Middleware\EnsureUserIsActive::class,
]);
})
    ->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {

        if (!$request->is('api/*')) {
            return null;
        }

        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Forbidden',
            ], 403);
        }

        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Request failed',
            ], $e->getStatusCode());
        }

        return null;
    });
})
->create();