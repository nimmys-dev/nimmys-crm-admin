<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the Lead module.
 *
 * Admins and Managers pass on their role; an Employee passes only with
 * lead_module_access switched on. Checked on every request rather than at
 * login, so revoking the flag takes effect immediately.
 *
 * Apply once the Lead module exists:
 *
 *   Route::middleware(['auth', 'web.access', 'leads'])->group(function () {
 *       Route::resource('leads', LeadController::class);
 *   });
 *
 *   // and on the mobile side
 *   Route::middleware(['auth:sanctum', 'mobile', 'leads'])->group(...);
 *
 * Registered as the `leads` alias in bootstrap/app.php.
 */
class EnsureLeadModuleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['message' => __('auth.unauthenticated')], 401)
                : redirect()->route('login');
        }

        // Routed through the gate rather than calling canAccessLeadModule()
        // directly, so middleware and policies share one decision point.
        if (! $user->can('leads.access')) {
            // 404 rather than 403 on the web: the brief asks for the module to
            // be hidden, and a 403 confirms it exists.
            if ($request->expectsJson()) {
                return response()->json(['message' => __('auth.lead_module_forbidden')], 403);
            }

            abort(404);
        }

        return $next($request);
    }
}
