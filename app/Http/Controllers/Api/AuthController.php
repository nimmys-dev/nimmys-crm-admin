<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'status_code' => 422,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find user by email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'status_code' => 404,
                'message' => 'User not found',
            ], 404);
        }
            // dd($request->password, $user->password);

        // Verify hashed password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'status_code' => 401,
                'message' => 'Incorrect password',
            ], 401);
        }

        // Delete old tokens
        $user->tokens()->delete();

        // Generate new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ], 200);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => false,
                'status_code' => 401,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Profile fetched successfully',
            'user' => [
                'id' => $user->id,
                'shop_id' => $user->shop_id,
                'employee_code' => $user->employee_code,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'photo' => $user->photo,
                'role' => $user->role,
                'status' => $user->status,
            ],
        ], 200);
    }

    public function logout(Request $request)
    {
        if (! $request->user() || ! $request->user()->currentAccessToken()) {
            return response()->json([
                'status' => false,
                'status_code' => 401,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Logout successful',
        ], 200);
    }
}
