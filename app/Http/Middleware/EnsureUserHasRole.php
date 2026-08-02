<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to one or more roles.
 *
 *   Route::middleware('role:admin')          — Admin only
 *   Route::middleware('role:admin,manager')  — either
 *   Route::middleware('admin')               — alias, see bootstrap/app.php
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $allowed = collect($roles)
            ->map(fn (string $role) => UserRole::tryFrom($role))
            ->filter();

        // An unrecognised role string is a programming error, not a denial.
        // Failing loudly here beats silently locking everyone out.
        if ($allowed->isEmpty()) {
            throw new \InvalidArgumentException(
                'role middleware called with no valid role: '.implode(',', $roles)
            );
        }

        abort_unless($user->hasRole($allowed->all()), 403, 'You do not have access to this area.');

        return $next($request);
    }
}
