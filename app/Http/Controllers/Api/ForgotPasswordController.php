<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    

    /*
    |--------------------------------------------------------------------------
    | WEB - Send Reset Link
    |--------------------------------------------------------------------------
    */

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = trim($request->email);

        $user = User::where('email', $email)->first();

        if (!$user) {
            return back()
                ->withErrors([
                    'email' => 'Email address not found.',
                ])
                ->withInput();
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


    /*
    |--------------------------------------------------------------------------
    | WEB - Show Reset Password Form
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | WEB - Reset Password
    |--------------------------------------------------------------------------
    */

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
                'email' => 'Invalid reset token.',
            ]);
        }

        $user = User::find($userId);

        if (!$user || $user->email !== $request->email) {
            return back()->withErrors([
                'email' => 'Invalid reset request.',
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()
            ->route('login')
            ->with('success', 'Password reset successfully.');
    }


    /*
    |--------------------------------------------------------------------------
    | API - Forgot Password
    |--------------------------------------------------------------------------
    */

    public function sendResetLinkApi(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = trim($request->email);

        $status = Password::sendResetLink([
            'email' => $email,
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'status' => true,
                'status_code' => 200,
                'message' => 'Password reset link has been sent to your email.',
            ], 200);
        }

        return response()->json([
            'status' => false,
            'status_code' => 422,
            'message' => __($status),
        ], 422);
    }


    /*
    |--------------------------------------------------------------------------
    | API - Reset Password
    |--------------------------------------------------------------------------
    */

    public function resetPasswordApi(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            [
                'email' => trim($request->email),
                'password' => $request->password,
                'password_confirmation' => $request->password_confirmation,
                'token' => $request->token,
            ],
            function (User $user, string $password) {

                $user->password = Hash::make($password);
                $user->save();

            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'status' => true,
                'status_code' => 200,
                'message' => 'Password reset successfully.',
            ], 200);
        }

        return response()->json([
            'status' => false,
            'status_code' => 422,
            'message' => __($status),
        ], 422);
    }
}