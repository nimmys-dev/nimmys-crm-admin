<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FirebaseTokenController extends Controller
{
    public function save(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => ['required', 'string', 'max:4096'],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $user->fcm_token = $request->input('fcm_token');
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'FCM token saved successfully.',
        ]);
    }
}