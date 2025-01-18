<?php

use App\Exceptions\AuthException;
use App\Traits\ApiResponseHelper;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append([
            \App\Http\Middleware\SecureHeaders::class
        ]);

        $middleware->alias([
            'jwt.middleware' => \App\Http\Middleware\AuthMiddleware::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return (new class {
                    use ApiResponseHelper;
                })->errorResponse('Route not found.', 404);
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return (new class {
                    use ApiResponseHelper;
                })->errorResponse('Method not allowed.', 405);
            }
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return (new class {
                    use ApiResponseHelper;
                })->errorResponse("Too many request, please slow down", 429);
            }
        });
        $exceptions->render(function (\RuntimeException $e, Request $request) {

            Log::debug($e->getMessage(), [
                'context' => [
                    'exception_class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'request_ip' => $request->ip(),
                    'request_url' => $request->fullUrl(),
                    'request_method' => $request->method(),
                    'user_agent' => $request->userAgent()
                ]
            ]);

            if ($request->is('api/*')) {
                Log::error($e->getMessage(), [
                    'context' => [
                        'exception_class' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                        'request_ip' => $request->ip(),
                        'request_url' => $request->fullUrl(),
                        'request_method' => $request->method(),
                        'user_agent' => $request->userAgent(),
                    ]
                ]);
                return (new class {
                    use ApiResponseHelper;
                })->errorResponse('Internal Server Error', 500);
            }
        });

        $exceptions->render(function (AuthException $e, Request $request) {
            if ($request->is('api/*')) {
                return (new class {
                    use ApiResponseHelper;
                })->errorResponse($e->getMessage(), 401);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return (new class {
                    use ApiResponseHelper;
                })->errorResponse($e->validator->errors()->toArray(), 400);
            }
        });

        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->is('api/*')) {
                Log::debug($e->getMessage(), [
                    'context' => [
                        'exception_class' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                        'request_ip' => $request->ip(),
                        'request_url' => $request->fullUrl(),
                        'request_method' => $request->method(),
                        'user_agent' => $request->userAgent()
                    ]
                ]);
                return (new class {
                    use ApiResponseHelper;
                })->errorResponse("Internal Server Error", 500);
            }
        });
    })->create();
