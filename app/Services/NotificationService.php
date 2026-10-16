<?php

namespace App\Services;

use App\Contracts\PushSender;
use App\Models\AppNotification;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    public function __construct(private PushSender $push) {}

    /** @param Collection<int,int>|array<int,int> $userIds */
    public function send(?User $sender, string $title, string $body, ?string $url, $userIds): AppNotification
    {
        $ids = collect($userIds)->unique()->values();
        $n = AppNotification::create(['sender_user_id' => $sender?->id, 'title' => $title, 'body' => $body, 'url' => $url]);
        $n->recipients()->attach($ids->mapWithKeys(fn ($id) => [$id => []])->all());

        $subs = PushSubscription::whereIn('user_id', $ids)->get()->groupBy('user_id');
        foreach ($ids as $uid) {
            $status = 'no_device';
            foreach ($subs[$uid] ?? [] as $sub) {
                $r = $this->push->send($sub, ['title' => $title, 'body' => $body, 'url' => $url ?: '/bildirimlerim']);
                if ($r === 'gone') {
                    $sub->delete();
                } elseif ($r === 'sent') {
                    $status = 'sent';
                } elseif ($status !== 'sent') {
                    $status = 'failed';
                }
            }
            $n->recipients()->updateExistingPivot($uid, ['push_status' => $status]);
        }

        return $n;
    }

    public function unreadCount(User $user): int
    {
        return $user->notifications()->wherePivotNull('read_at')->count();
    }
}
