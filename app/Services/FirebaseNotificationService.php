<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
class FirebaseNotificationService
{
    private function accessToken(): string
    {
        $credentialsPath = base_path(
            env(
                'FIREBASE_CREDENTIALS',
                'storage/app/firebase/firebase-credentials.json'
            )
        );

        if (!file_exists($credentialsPath)) {
            throw new \RuntimeException(
                'Firebase credentials file not found.'
            );
        }

        $credentials = json_decode(
            file_get_contents($credentialsPath),
            true
        );

        if (
            empty($credentials['client_email']) ||
            empty($credentials['private_key']) ||
            empty($credentials['token_uri'])
        ) {
            throw new \RuntimeException(
                'Invalid Firebase service account JSON.'
            );
        }

        $cacheKey = 'firebase_access_token_' .
            md5($credentials['client_email']);

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(50),
            function () use ($credentials) {
                $now = time();

                $base64url = static function (string $value): string {
                    return rtrim(
                        strtr(base64_encode($value), '+/', '-_'),
                        '='
                    );
                };

                $header = $base64url(json_encode([
                    'alg' => 'RS256',
                    'typ' => 'JWT',
                ]));

                $claim = $base64url(json_encode([
                    'iss' => $credentials['client_email'],
                    'scope' =>
                        'https://www.googleapis.com/auth/firebase.messaging',
                    'aud' => $credentials['token_uri'],
                    'iat' => $now,
                    'exp' => $now + 3600,
                ]));

                $unsigned = $header . '.' . $claim;

                $privateKey = openssl_pkey_get_private(
                    $credentials['private_key']
                );

                if (!$privateKey) {
                    throw new \RuntimeException(
                        'Unable to load Firebase private key.'
                    );
                }

                if (!openssl_sign(
                    $unsigned,
                    $signature,
                    $privateKey,
                    OPENSSL_ALGO_SHA256
                )) {
                    throw new \RuntimeException(
                        'Firebase JWT signing failed.'
                    );
                }

                $jwt = $unsigned . '.' . $base64url($signature);

                $response = Http::asForm()
                    ->timeout(20)
                    ->post($credentials['token_uri'], [
                        'grant_type' =>
                            'urn:ietf:params:oauth:grant-type:jwt-bearer',
                        'assertion' => $jwt,
                    ]);

                if (!$response->successful()) {
                    throw new \RuntimeException(
                        'Firebase OAuth failed: ' .
                        $response->body()
                    );
                }

                $token = $response->json('access_token');

                if (!$token) {
                    throw new \RuntimeException(
                        'Firebase OAuth returned no access token.'
                    );
                }

                return $token;
            }
        );
    }

    public function sendToUser(
        User $user,
        string $title,
        string $body,
        array $data = []
    ): bool {
        $fcmToken = trim((string) $user->fcm_token);

        if ($fcmToken === '') {
            Log::warning('FCM token missing', [
                'user_id' => $user->id,
            ]);

            return false;
        }

        try {
            $projectId = env('FIREBASE_PROJECT_ID');

            if (!$projectId) {
                throw new \RuntimeException(
                    'FIREBASE_PROJECT_ID is missing.'
                );
            }

            $response = Http::withToken($this->accessToken())
                ->acceptJson()
                ->timeout(30)
                ->post(
                    "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                    [
                        'message' => [
                            'token' => $fcmToken,
                            'notification' => [
                                'title' => $title,
                                'body' => $body,
                            ],
                            'data' => array_map(
                                static fn ($value) =>
                                    (string) $value,
                                $data
                            ),
                            'android' => [
                                'priority' => 'HIGH',
                                'notification' => [
                                    'channel_id' => 'task_notifications',
                                ],
                            ],
                        ],
                    ]
                );

            if (!$response->successful()) {
                Log::error('FCM send failed', [
                    'user_id' => $user->id,
                    'http_status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return false;
            }

            Log::info('FCM notification accepted', [
                'user_id' => $user->id,
                'response' => $response->json(),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('FCM notification exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
