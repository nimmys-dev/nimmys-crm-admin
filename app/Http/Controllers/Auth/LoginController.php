<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Authenticate and start the session.
     *
     * Role and status checks live in LoginRequest::authenticate(), keeping
     * this controller to session handling and the redirect decision.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Rotates the session ID, defeating session fixation.
        $request->session()->regenerate();

        $user = $request->user();

        $user->recordLogin($request->ip());

        return redirect()->intended(route($user->role->dashboardRoute()));
    }

    /**
     * End the session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
