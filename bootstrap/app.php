<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->alias([
            // Parameterised: role:admin,manager
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,

            // Convenience aliases — same class, role baked in.
            'admin' => \App\Http\Middleware\EnsureUserHasRole::class.':admin',
            'manager' => \App\Http\Middleware\EnsureUserHasRole::class.':manager',

            // Surface guards.
            'web.access' => \App\Http\Middleware\EnsureUserCanAccessWeb::class,
            'mobile' => \App\Http\Middleware\EnsureUserCanAccessMobile::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,

            // Module guards.
            'leads' => \App\Http\Middleware\EnsureLeadModuleAccess::class,
        ]);

        // Invalidates a session when the user's password changes elsewhere,
        // so a stolen session dies the moment the password is reset. Uses the
        // framework's own API so ordering within the web group is correct.
        $middleware->authenticateSessions();

        $middleware->redirectGuestsTo(fn () => route('login'));

    })
    ->withExceptions(function (Exceptions $exceptions) {
        
        $exceptions->render(function (AuthenticationException $e, Request $request) {

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'status_code' => 401,
                    'message' => 'Unauthenticated.'
                ], 401);
            }

        });
    })->create();
