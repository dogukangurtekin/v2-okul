<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $fillable = ['quiz_id', 'position', 'text', 'options', 'correct_index', 'duration_sec', 'xp', 'double_xp'];

    protected $casts = ['options' => 'array', 'double_xp' => 'boolean'];
}
