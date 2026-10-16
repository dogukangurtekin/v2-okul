<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginToken extends Model
{
    protected $fillable = ['kind', 'user_id', 'token_hash', 'poll_hash', 'created_by', 'approved_by', 'request_ip', 'request_ua', 'approved_at', 'used_at', 'expires_at'];

    protected $casts = ['approved_at' => 'datetime', 'used_at' => 'datetime', 'expires_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }

    public function usable(): bool
    {
        return ! $this->used_at && $this->expires_at->isFuture();
    }
}
