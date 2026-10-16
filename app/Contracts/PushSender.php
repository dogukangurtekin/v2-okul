<?php

namespace App\Contracts;

use App\Models\PushSubscription;

interface PushSender
{
    /** @return string sent | gone (abonelik gecersiz, silinmeli) | failed */
    public function send(PushSubscription $sub, array $payload): string;
}
