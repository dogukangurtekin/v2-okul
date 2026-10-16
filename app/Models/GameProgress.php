<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameProgress extends Model
{
    protected $table = 'game_progress';

    protected $fillable = ['student_id', 'game_slug', 'level', 'best_seconds', 'best_score', 'plays', 'xp_awarded'];
}
