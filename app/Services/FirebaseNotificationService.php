<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseNotificationService
{
    protected $messaging;

    public function __construct()
    {
        $credentials = config('services.firebase.credentials');

        // Convert relative path to absolute path
        if (!str_starts_with($credentials, DIRECTORY_SEPARATOR)
            && !preg_match('/^[A-Za-z]:[\\\\\/]/', $credentials)) {

            $credentials = base_path($credentials);
        }

        if (!file_exists($credentials)) {
            throw new \Exception(
                'Firebase credentials file not found: ' . $credentials
            );
        }

        $factory = (new Factory)
            ->withServiceAccount($credentials);

        $this->messaging = $factory->createMessaging();
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

                Log::warning('FCM token not found', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                ]);

                return false;
            }

            $notification = Notification::create(
                $title,
                $body
            );

            $message = CloudMessage::withTarget(
                'token',
                $token
            )
                ->withNotification($notification)
                ->withData($data);

            $this->messaging->send($message);

            Log::info('FCM notification sent', [
                'user_id' => $user->id,
                'title' => $title,
            ]);

            return true;

        } catch (\Throwable $e) {

            Log::error('FCM notification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}