<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    protected string $projectId = '';
    protected string $credentialsPath = '';

    public function __construct()
    {
        $credentials = trim((string) config('services.firebase.credentials', ''));

        if ($credentials === '') {
            return;
        }

        if (!str_starts_with($credentials, DIRECTORY_SEPARATOR)
            && !preg_match('/^[A-Za-z]:[\\\\\/]/', $credentials)) {
            $credentials = base_path($credentials);
        }

        $this->credentialsPath = $credentials;
    }

    
    private function getAccessToken(): ?string
    {
        $credentialsPath = $this->credentialsPath;

        if ($credentialsPath === '') {
            Log::error('FCM credentials are not configured. Set FIREBASE_CREDENTIALS to the service-account JSON path.');
            return null;
        }

        if (is_dir($credentialsPath)) {
            Log::error('FCM Error: Given path is a directory, not a file: ' . $credentialsPath);
            return null;
        }

        if (!file_exists($credentialsPath)) {
            Log::error('FCM Error: Credentials file missing at: ' . $credentialsPath);
            return null;
        }

        $credentialsJson = file_get_contents($credentialsPath);
        $jsonKey = is_string($credentialsJson) ? json_decode($credentialsJson, true) : null;
        
        if (!is_array($jsonKey) || !isset($jsonKey['project_id'], $jsonKey['client_email'], $jsonKey['private_key'])) {
            Log::error('FCM Error: Invalid JSON structure in credentials file.');
            return null;
        }

        $this->projectId = $jsonKey['project_id'] ?? '';

        // JWT Header
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));

        // JWT Claim Set
        $now = time();
        $claimSet = $this->base64UrlEncode(json_encode([
            'iss' => $jsonKey['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ], JSON_THROW_ON_ERROR));

        // Sign JWT with Private Key
        $signature = '';
        if (!openssl_sign(
            $header . '.' . $claimSet,
            $signature,
            $jsonKey['private_key'],
            'SHA256'
        )) {
            Log::error('FCM Error: Unable to sign the OAuth JWT with the service-account private key.');
            return null;
        }

        $jwt = $header . '.' . $claimSet . '.' . $this->base64UrlEncode($signature);

        // Fetch Access Token from Google
        try {
            $response = Http::asForm()
                ->connectTimeout(10)
                ->timeout(30)
                ->retry(2, 250)
                ->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);
        } catch (\Throwable $exception) {
            Log::error('FCM OAuth request failed.', ['error' => $exception->getMessage()]);
            return null;
        }

        if (!$response->successful() || blank($response->json('access_token'))) {
            Log::error('FCM OAuth request was rejected.', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            return null;
        }

        return $response->json()['access_token'] ?? null;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
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
                ->connectTimeout(10)
                ->timeout(30)
                ->retry(2, 250)
                ->post($url, [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => array_map('strval', $data), 
                    ],
                ]);

            if ($response->successful()) {
                Log::info('FCM notification sent successfully', ['user_id' => $user->id]);
                return true;
            }

            Log::error('FCM notification failed', [
                'user_id' => $user->id,
                'status' => $response->status(),
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
