<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps Employees out of the web portal on every request, not just at login.
 *
 * The login check alone is not enough: a user whose role is changed to
 * Employee while holding a live session would otherwise keep browsing until
 * the session expired. This logs them straight out instead.
 */
class EnsureUserCanAccessWeb
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->canAccessWeb()) {
            $message = $user->isActive()
                ? __('auth.web_forbidden')
                : __('auth.inactive');

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
