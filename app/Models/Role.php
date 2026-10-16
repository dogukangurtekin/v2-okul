<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public const ADMIN = 'admin';
    public const TEACHER = 'teacher';
    public const STUDENT = 'student';

    protected $fillable = ['name', 'slug'];
}
