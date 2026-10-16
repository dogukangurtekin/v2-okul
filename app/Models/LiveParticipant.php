<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveParticipant extends Model
{
    protected $fillable = ['live_session_id', 'student_id', 'name', 'score', 'progress', 'wpm', 'accuracy', 'xp_earned', 'rank_no', 'joined_at_ms', 'finished_at_ms'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class, 'live_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
