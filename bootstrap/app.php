<?php

use App\Traits\ApiResponseTrait;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return (new class {
                use ApiResponseTrait;
            })->errorResponse(
                message: 'Erro de validação.',
                status: 422,
                errors: $e->errors(),
            );
        });

        $exceptions->render(function (AuthenticationException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return (new class {
                use ApiResponseTrait;
            })->errorResponse(
                message: 'Não autenticado.',
                status: 401,
            );
        });

        $exceptions->render(function (UrlGenerationException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return (new class {
                use ApiResponseTrait;
            })->errorResponse(
                message: 'Não autenticado.',
                status: 401,
            );
        });

        $exceptions->render(function (RouteNotFoundException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return (new class {
                use ApiResponseTrait;
            })->errorResponse(
                message: 'Não autenticado.',
                status: 401,
            );
        });

        $exceptions->render(function (AuthorizationException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return (new class {
                use ApiResponseTrait;
            })->errorResponse(
                message: 'Não autorizado.',
                status: 403,
            );
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return (new class {
                use ApiResponseTrait;
            })->errorResponse(
                message: 'Registro não encontrado.',
                status: 404,
            );
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return (new class {
                use ApiResponseTrait;
            })->errorResponse(
                message: 'Endpoint não encontrado.',
                status: 404,
            );
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return (new class {
                use ApiResponseTrait;
            })->errorResponse(
                message: 'Método não permitido.',
                status: 405,
            );
        });

        $exceptions->render(function (HttpException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return (new class {
                use ApiResponseTrait;
            })->errorResponse(
                message: match ($e->getStatusCode()) {
                    401 => 'Não autenticado.',
                    403 => 'Não autorizado.',
                    404 => 'Registro não encontrado.',
                    405 => 'Método não permitido.',
                    default => 'Erro na requisição.',
                },
                status: $e->getStatusCode(),
            );
        });

        $exceptions->render(function (Throwable $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            $message = config('app.debug') ? $e->getMessage() : 'Erro interno do servidor.';

            return (new class {
                use ApiResponseTrait;
            })->errorResponse(
                message: $message,
                status: 500,
            );
        });
    })->create();
