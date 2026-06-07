<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class PushNotificationService
{
    protected Messaging $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    public function sendToUsers(array|Collection $recipients, string $title, string $body, array $data = []): array
    {
        $userIds = $recipients instanceof Collection ? $recipients->all() : $recipients;

        $tokens = User::whereIn('id', $userIds)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->filter()
            ->values()
            ->all();

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        $result = ['success' => 0, 'failure' => 0, 'errors' => []];

        if (empty($tokens)) {
            return $result;
        }

        $chunks = array_chunk($tokens, 500);

        foreach ($chunks as $chunk) {
            $message = CloudMessage::new()
                ->withNotification(FcmNotification::create($title, $body))
                ->withData($data);

            $report = $this->messaging->sendMulticast($message, $chunk);

            $result['success'] += $report->successes()->count();
            $result['failure'] += $report->failures()->count();

            foreach ($report->failures()->getItems() as $failure) {
                $result['errors'][] = $failure->error()->getMessage();
            }
        }

        return $result;
    }
}


