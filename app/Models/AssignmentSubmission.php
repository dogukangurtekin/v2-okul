<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentSubmission extends Model
{
    protected $fillable = ['assignment_id', 'student_id', 'started_at', 'completed_at', 'reached_level', 'completion_seconds', 'xp_awarded', 'payload'];

    protected $casts = ['started_at' => 'datetime', 'completed_at' => 'datetime', 'payload' => 'array'];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }
}
