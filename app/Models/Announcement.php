<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    public const AUDIENCES = ['all' => 'Herkes', 'students' => 'Öğrenciler', 'teachers' => 'Öğretmenler'];

    protected $fillable = ['title', 'content', 'audience', 'published_by', 'published_at'];

    protected $casts = ['published_at' => 'datetime'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /** Kullanicinin gorebilecegi yayimlanmis duyurular. */
    public function scopeVisibleTo($q, User $user)
    {
        $aud = ['all'];
        if ($user->hasRole('student')) {
            $aud[] = 'students';
        } elseif ($user->hasRole('teacher')) {
            $aud[] = 'teachers';
        } else {
            $aud = array_keys(self::AUDIENCES); // yonetici hepsini gorur
        }

        return $q->whereIn('audience', $aud)->whereNotNull('published_at')->where('published_at', '<=', now())->latest('published_at');
    }
}
