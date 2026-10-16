<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportRequest extends Model
{
    public const CATEGORIES = ['technical' => 'Teknik sorun', 'content' => 'İçerik', 'account' => 'Hesap', 'other' => 'Diğer'];
    public const STATUSES = ['open' => 'Açık', 'answered' => 'Yanıtlandı', 'closed' => 'Kapalı'];

    protected $fillable = ['sender_user_id', 'guest_name', 'guest_email', 'source', 'subject', 'message', 'category', 'priority', 'status', 'read_at', 'closed_at', 'archived_at'];

    protected $casts = ['read_at' => 'datetime', 'closed_at' => 'datetime', 'archived_at' => 'datetime'];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportReply::class)->oldest();
    }

    public function getSenderNameAttribute(): string
    {
        return $this->sender?->name ?? ($this->guest_name.' (misafir)');
    }
}
