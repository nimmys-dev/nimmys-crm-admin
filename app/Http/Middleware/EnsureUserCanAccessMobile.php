<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the future mobile API routes.
 *
 * Not yet applied to any route — routes/api.php does not exist until Sanctum
 * is installed. It is written now so the mobile surface has the same
 * enforcement shape as the web one, and returns JSON rather than redirects.
 *
 * Intended use once the API lands:
 *
 *   Route::middleware(['auth:sanctum', 'mobile'])->group(base_path('routes/api.php'));
 */
class EnsureUserCanAccessMobile
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => __('auth.unauthenticated')], 401);
        }

        if (! $user->canAccessMobile()) {
            return response()->json(['message' => __('auth.inactive')], 403);
        }

        return $next($request);
    }
}
