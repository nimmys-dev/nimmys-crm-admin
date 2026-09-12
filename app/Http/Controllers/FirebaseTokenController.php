<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FirebaseTokenController extends Controller
{
    public function save(Request $request)
    {
        $validated = $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $user->forceFill(['fcm_token' => $validated['fcm_token']])->save();

        Log::info('FCM token saved.', [
            'user_id' => $user->id,
            'token_length' => strlen($validated['fcm_token']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Firebase token saved successfully.',
        ]);
    }
}
