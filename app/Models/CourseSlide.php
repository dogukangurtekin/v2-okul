<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseSlide extends Model
{
    public const TYPES = ['text' => 'Metin', 'image' => 'Görsel', 'video' => 'Video', 'code' => 'Kod', 'quiz' => 'Soru'];

    protected $fillable = ['course_id', 'position', 'type', 'title', 'content'];

    protected $casts = ['content' => 'array'];
}
