<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withProviders([
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                if ($e instanceof AuthenticationException) {
                    return response()->json(['message' => 'Unauthenticated.'], 401);
                }

                if ($e instanceof HttpException) {
                    $message = $e->getMessage() ?: 'Forbidden';
                    return response()->json(['message' => $message], $e->getStatusCode());
                }

                // For other exceptions in debug mode, let the default handler do its thing.
                // In production, you'd want to return a generic 500 error.
                // if (!config('app.debug')) {
                //     return response()->json(['message' => 'Server Error'], 500);
                // }
            }
        });
    })->create();
