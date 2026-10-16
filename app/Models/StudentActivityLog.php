<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['student_id', 'route_name', 'label', 'ip', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];
}
