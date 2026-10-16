<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XpEntry extends Model
{
    protected $table = 'xp_ledger';

    protected $fillable = ['student_id', 'delta', 'reason', 'source_type', 'source_id', 'granted_by'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
