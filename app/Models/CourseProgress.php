<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseProgress extends Model
{
    protected $table = 'course_progress';

    protected $fillable = ['student_id', 'course_id', 'slide_index', 'state', 'completed_at', 'xp_awarded'];

    protected $casts = ['state' => 'array', 'completed_at' => 'datetime'];
}
