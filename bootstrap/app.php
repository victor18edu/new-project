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
                code: 422,
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
                code: 401,
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
                code: 401,
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
                code: 401,
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
                code: 403,
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
                code: 404,
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
                code: 404,
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
                code: 405,
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
                code: 500,
            );
        });
    })->create();
