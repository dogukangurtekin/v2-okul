<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use SoftDeletes;

    protected $fillable = ['type', 'course_id', 'game_slug', 'title', 'details', 'due_date', 'level_from', 'level_to', 'xp_reward', 'attachment_path', 'created_by'];

    protected $casts = ['due_date' => 'date'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'assignment_school_class');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }
}
