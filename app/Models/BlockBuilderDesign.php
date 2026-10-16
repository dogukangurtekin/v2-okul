<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockBuilderDesign extends Model
{
    protected $fillable = ['user_id', 'name', 'blocks'];

    protected $casts = ['blocks' => 'array'];
}
