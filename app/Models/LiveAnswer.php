<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveAnswer extends Model
{
    protected $fillable = ['live_session_id', 'live_participant_id', 'question_index', 'answer_index', 'is_correct', 'points', 'answered_at_ms'];

    protected $casts = ['is_correct' => 'boolean'];
}
