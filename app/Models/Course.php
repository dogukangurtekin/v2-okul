<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = ['parent_id', 'name', 'code', 'description', 'category', 'difficulty', 'curriculum', 'cover_path', 'teacher_id', 'created_by', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'curriculum' => 'array'];

    public function slides(): HasMany
    {
        return $this->hasMany(CourseSlide::class)->orderBy('position');
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'course_school_class');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function scopeVisibleToStudent($query, Student $student)
    {
        return $query->where('is_active', true)
            ->whereHas('classes', fn ($q) => $q->where('school_classes.id', $student->school_class_id));
    }
}
