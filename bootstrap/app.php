<?php

use Illuminate\Foundation\Application;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add the frontend redirect middleware to web routes
        $middleware->web(append: [
            \App\Http\Middleware\FrontendRedirectMiddleware::class,
        ]);
    })
    ->withProviders([
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                $success = false;
                $error = 'Server Error';
                $message = 'An unexpected error occurred.';
                $statusCode = 500;
                $details = null;

                if ($e instanceof HttpException) {
                    $statusCode = $e->getStatusCode();
                    $message = $e->getMessage() ?: 'Request failed.';
                    $error = match ($statusCode) {
                        401 => 'Unauthorized',
                        403 => 'Forbidden',
                        404 => 'Not Found',
                        409 => 'Conflict',
                        422 => 'Validation Error',
                        default => 'Request Error',
                    };
                }

                if ($e instanceof ValidationException) {
                    $statusCode = 422;
                    $error = 'Validation failed';
                    $message = $e->getMessage();
                    $details = $e->errors();
                } elseif ($e instanceof AuthenticationException) {
                    $statusCode = 401;
                    $error = 'Unauthenticated';
                    $message = 'Authentication failed.';
                }

                if (config('app.debug')) {
                    $details['exception'] = get_class($e);
                    $details['trace'] = array_slice($e->getTrace(), 0, 5);
                }

                $response = [
                    'success' => $success,
                    'error' => $error,
                    'message' => $message,
                ];

                if ($details) {
                    $response['details'] = $details;
                }

                return response()->json($response, $statusCode);
            }
        });
    })->create();
