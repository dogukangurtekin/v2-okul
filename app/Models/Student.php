<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = ['user_id', 'student_no', 'school_class_id', 'parent_name', 'parent_phone', 'birth_date', 'xp_balance', 'xp_total', 'current_avatar_id', 'last_seen_at', 'last_path', 'total_seconds'];

    protected $casts = ['birth_date' => 'date', 'last_seen_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function xpEntries(): HasMany
    {
        return $this->hasMany(XpEntry::class);
    }

    public function avatar(): BelongsTo
    {
        return $this->belongsTo(Avatar::class, 'current_avatar_id');
    }

    public function avatars(): BelongsToMany
    {
        return $this->belongsToMany(Avatar::class, 'student_avatar')->withPivot('unlocked_at');
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'student_badge')->withPivot('awarded_at');
    }
}
