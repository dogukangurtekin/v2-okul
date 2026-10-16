<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AppNotification extends Model
{
    protected $fillable = ['sender_user_id', 'title', 'body', 'url'];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'app_notification_user')->withPivot('read_at', 'push_status');
    }
}
