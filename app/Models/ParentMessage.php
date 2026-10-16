<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentMessage extends Model
{
    protected $fillable = ['student_id', 'sent_by', 'channel', 'status', 'detail'];
}
