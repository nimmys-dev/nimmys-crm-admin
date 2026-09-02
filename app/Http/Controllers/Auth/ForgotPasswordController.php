<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class ForgotPasswordController extends Controller
{
    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

  

public function sendResetLink(Request $request)
{
    $request->validate([
        'email' => ['required', 'email'],
    ]);

    $email = trim($request->email);

    $user = User::where('email', $email)->first();

    if (!$user) {
        return back()->withErrors([
            'email' => 'Email address not found.'
        ])->withInput();
    }

    $token = encrypt($user->id);

    $resetUrl = route('password.reset', [
        'token' => $token,
        'email' => $user->email,
    ]);

    Mail::raw(
        "Click the link below to reset your password:\n\n" . $resetUrl,
        function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Password Reset Link');
        }
    );

    return back()->with(
        'success',
        'Password reset link has been sent to your email.'
    );
}

    public function showResetForm(Request $request, string $token)
{
    try {
        $userId = decrypt($token);
    } catch (\Exception $e) {
        abort(403, 'Invalid reset token.');
    }

    $user = User::find($userId);

    if (!$user) {
        abort(404, 'User not found.');
    }

    if ($request->email !== $user->email) {
        abort(403, 'Invalid reset request.');
    }

    return view('auth.reset-password', [
        'token' => $token,
        'email' => $user->email,
    ]);
}

  public function resetPassword(Request $request)
{
    $request->validate([
        'token' => ['required'],
        'email' => ['required', 'email'],
        'password' => ['required', 'confirmed', 'min:8'],
    ]);

    try {
        $userId = decrypt($request->token);
    } catch (\Exception $e) {
        return back()->withErrors([
            'email' => 'Invalid reset token.'
        ]);
    }

    $user = User::find($userId);

    if (!$user || $user->email !== $request->email) {
        return back()->withErrors([
            'email' => 'Invalid reset request.'
        ]);
    }

    $user->password = Hash::make($request->password);
    $user->save();

    return redirect()
        ->route('login')
        ->with('success', 'Password reset successfully.');
}
}