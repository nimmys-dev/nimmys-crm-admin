<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FirebaseTokenController extends Controller
{
    public function save(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        auth()->user()->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Firebase token saved successfully.',
        ]);
    }
}