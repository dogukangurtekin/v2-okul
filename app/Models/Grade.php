<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    protected $fillable = ['student_id', 'course_id', 'exam_type', 'title', 'score', 'exam_date', 'term', 'recorded_by'];

    protected $casts = ['exam_date' => 'date:Y-m-d', 'score' => 'float'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
