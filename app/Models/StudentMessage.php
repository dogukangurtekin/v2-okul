<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMessage extends Model
{
    protected $fillable = ['student_id', 'recipient_type', 'recipient_user_id', 'body', 'reply_body', 'replied_by', 'replied_at', 'student_read_at', 'staff_deleted_at'];

    protected $casts = ['replied_at' => 'datetime', 'student_read_at' => 'datetime', 'staff_deleted_at' => 'datetime'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function replier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }
}
