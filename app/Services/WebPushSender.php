<?php

namespace App\Services;

use App\Contracts\PushSender;
use App\Models\PushSubscription;

/** minishlink/web-push kuruluysa ve VAPID anahtarlari varsa gercek push gonderir; aksi halde sessizce 'failed' doner. */
class WebPushSender implements PushSender
{
    public function available(): bool
    {
        return class_exists(\Minishlink\WebPush\WebPush::class) && config('webpush.public_key') && config('webpush.private_key');
    }

    public function send(PushSubscription $sub, array $payload): string
    {
        if (! $this->available()) {
            return 'failed';
        }
        $webPush = new \Minishlink\WebPush\WebPush(['VAPID' => [
            'subject' => config('webpush.subject'), 'publicKey' => config('webpush.public_key'), 'privateKey' => config('webpush.private_key'),
        ]]);
        $report = $webPush->sendOneNotification(
            \Minishlink\WebPush\Subscription::create(['endpoint' => $sub->endpoint, 'publicKey' => $sub->p256dh, 'authToken' => $sub->auth]),
            json_encode($payload, JSON_UNESCAPED_UNICODE),
        );

        return $report->isSuccess() ? 'sent' : ($report->isSubscriptionExpired() ? 'gone' : 'failed');
    }
}
