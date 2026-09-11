<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    protected string $projectId;
    protected string $credentialsPath;

    public function __construct()
    {
        $credentials = config('services.firebase.credentials');

        if (!str_starts_with($credentials, DIRECTORY_SEPARATOR)
            && !preg_match('/^[A-Za-z]:[\\\\\/]/', $credentials)) {
            $credentials = base_path($credentials);
        }

        $this->credentialsPath = $credentials;
    }

    /**
     * Google Access Token ഉണ്ടാക്കാൻ (OAuth2)
     */
    private function getAccessToken(): ?string
    {
        $credentialsPath = $this->credentialsPath;

        // പാത്ത് ഒരു Directory ആണോ അതോ ഫയൽ ആണോ എന്ന് ചെക്ക് ചെയ്യുന്നു
        if (is_dir($credentialsPath)) {
            Log::error('FCM Error: Given path is a directory, not a file: ' . $credentialsPath);
            return null;
        }

        if (!file_exists($credentialsPath)) {
            Log::error('FCM Error: Credentials file missing at: ' . $credentialsPath);
            return null;
        }

        $jsonKey = json_decode(file_get_contents($credentialsPath), true);
        
        if (!$jsonKey) {
            Log::error('FCM Error: Invalid JSON structure in credentials file.');
            return null;
        }

        $this->projectId = $jsonKey['project_id'] ?? '';

        // JWT Header
        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));

        // JWT Claim Set
        $now = time();
        $claimSet = base64_encode(json_encode([
            'iss' => $jsonKey['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ]));

        // Sign JWT with Private Key
        $signature = '';
        openssl_sign(
            $header . '.' . $claimSet,
            $signature,
            $jsonKey['private_key'],
            'SHA256'
        );

        $jwt = $header . '.' . $claimSet . '.' . base64_encode($signature);

        // Fetch Access Token from Google
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        return $response->json()['access_token'] ?? null;
    }

    /**
     * Send notification to a single user
     */
    public function sendToUser(
        User $user,
        string $title,
        string $body,
        array $data = []
    ): bool {
        try {
            $token = $user->fcm_token;

            if (empty($token)) {
                Log::warning('FCM token not found', ['user_id' => $user->id]);
                return false;
            }

            $accessToken = $this->getAccessToken();

            if (!$accessToken) {
                Log::error('Failed to generate FCM access token');
                return false;
            }

            // Firebase HTTP v1 API Request
            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $response = Http::withToken($accessToken)
                ->post($url, [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => array_map('strval', $data), // Data values string ആയിരിക്കണം
                    ],
                ]);

            if ($response->successful()) {
                Log::info('FCM notification sent successfully', ['user_id' => $user->id]);
                return true;
            }

            Log::error('FCM notification failed', [
                'user_id' => $user->id,
                'response' => $response->json(),
            ]);

            return false;

        } catch (\Throwable $e) {
            Log::error('FCM notification Exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}