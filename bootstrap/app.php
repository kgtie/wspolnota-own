<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\Api\ApiAuthenticate;
use App\Http\Middleware\Api\EnsureApiEmailVerified;
use App\Http\Middleware\Api\EnsureApiParishApproved;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsSuperAdmin;
use App\Http\Middleware\ForceEmailVerification;
use App\Http\Middleware\RedirectWwwToApex;
use App\Support\Api\ErrorCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'superadmin' => EnsureUserIsSuperAdmin::class,
            'api.auth' => ApiAuthenticate::class,
            'api.verified' => EnsureApiEmailVerified::class,
            'api.parish_approved' => EnsureApiParishApproved::class,
        ]);
        $middleware->web(prepend: [
            RedirectWwwToApex::class,
        ]);
        $middleware->web(append: [
            ForceEmailVerification::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'error' => [
                        'code' => ErrorCode::VALIDATION_ERROR,
                        'message' => 'Nieprawidłowe dane',
                        'details' => (object) $e->errors(),
                    ],
                ], 422);
            }

            if ($e instanceof ApiException) {
                return response()->json([
                    'error' => [
                        'code' => $e->errorCode,
                        'message' => $e->getMessage(),
                        'details' => (object) $e->details,
                    ],
                ], $e->httpStatus);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'error' => [
                        'code' => ErrorCode::AUTH_UNAUTHENTICATED,
                        'message' => 'Brak autoryzacji.',
                        'details' => (object) [],
                    ],
                ], 401);
            }

            if ($e instanceof AuthorizationException) {
                return response()->json([
                    'error' => [
                        'code' => ErrorCode::FORBIDDEN,
                        'message' => 'Brak uprawnień.',
                        'details' => (object) [],
                    ],
                ], 403);
            }

            if ($e instanceof ModelNotFoundException) {
                return response()->json([
                    'error' => [
                        'code' => ErrorCode::NOT_FOUND,
                        'message' => 'Nie znaleziono zasobu.',
                        'details' => (object) [],
                    ],
                ], 404);
            }

            if ($e instanceof NotFoundHttpException) {
                return response()->json([
                    'error' => [
                        'code' => ErrorCode::NOT_FOUND,
                        'message' => 'Nie znaleziono zasobu.',
                        'details' => (object) [],
                    ],
                ], 404);
            }

            if ($e instanceof MethodNotAllowedHttpException) {
                return response()->json([
                    'error' => [
                        'code' => ErrorCode::FORBIDDEN,
                        'message' => 'Metoda HTTP nie jest obsługiwana dla tego zasobu.',
                        'details' => (object) [],
                    ],
                ], 405);
            }

            if ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 429) {
                return response()->json([
                    'error' => [
                        'code' => ErrorCode::RATE_LIMITED,
                        'message' => 'Zbyt wiele żądań. Spróbuj ponownie później.',
                        'details' => (object) [],
                    ],
                ], 429);
            }

            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'error' => [
                        'code' => $e->getStatusCode() === 404 ? ErrorCode::NOT_FOUND : ErrorCode::FORBIDDEN,
                        'message' => $e->getStatusCode() === 404 ? 'Nie znaleziono zasobu.' : 'Żądanie nie może zostać obsłużone.',
                        'details' => (object) [],
                    ],
                ], $e->getStatusCode());
            }

            return response()->json([
                'error' => [
                    'code' => ErrorCode::INTERNAL_ERROR,
                    'message' => 'Wystąpił błąd serwera.',
                    'details' => (object) [],
                ],
            ], 500);
        });
    })->create();
