<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveSession extends Model
{
    public const TYPES = ['quiz' => 'Canlı Quiz', 'competition' => 'Oyun Yarışması', 'typing' => 'Klavye Yarışı'];

    protected $fillable = ['type', 'host_user_id', 'school_class_id', 'quiz_id', 'join_code', 'status', 'is_locked', 'current_index', 'config', 'started_at_ms', 'ends_at_ms', 'finished_at_ms', 'xp_granted_at'];

    protected $casts = ['config' => 'array', 'is_locked' => 'boolean', 'xp_granted_at' => 'datetime'];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(LiveParticipant::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(LiveAnswer::class);
    }
}
