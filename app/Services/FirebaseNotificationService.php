<?php

namespace App\Services;

use App\Models\User;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log; // 👈 Log ചേർക്കുക

class FirebaseNotificationService
{
    protected Messaging $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        if (!$user->fcm_token) {
            Log::warning("FCM Failed: User {$user->id} has no FCM Token.");
            return false;
        }

        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification($notification)
                ->withData($data);

            $this->messaging->send($message);

            // SUCCESS LOG
            Log::info("FCM Notification Sent Successfully to User ID: {$user->id}");
            return true;

        } catch (\Exception $e) {
            // ERROR LOG
            Log::error("FCM Notification Error for User ID {$user->id}: " . $e->getMessage());
            return false;
        }
    }
}