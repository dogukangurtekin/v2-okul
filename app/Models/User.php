<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = ['role_id', 'name', 'email', 'password', 'is_active', 'last_login_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'force_logout_at' => 'datetime',
            'dashboard_layout' => 'array',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function notifications()
    {
        return $this->belongsToMany(AppNotification::class, 'app_notification_user')->withPivot('read_at', 'push_status');
    }

    public function hasRole(string ...$slugs): bool
    {
        return in_array($this->role?->slug, $slugs, true);
    }
}
