<?php

namespace App\Support;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;

/** Yonetici her seyi, ogretmen yalniz kendi siniflarini gorur. */
class StaffScope
{
    public static function classes(User $user)
    {
        return SchoolClass::query()->orderBy('grade_level')->orderBy('section')
            ->when(! $user->hasRole('admin'), fn ($q) => $q->where('teacher_id', $user->teacher?->id ?? 0));
    }

    public static function students(User $user)
    {
        return Student::query()->when(! $user->hasRole('admin'), fn ($q) => $q->whereIn('school_class_id', self::classes($user)->select('id')));
    }

    public static function authorizeStudent(User $user, Student $student): void
    {
        abort_unless(self::students($user)->whereKey($student->id)->exists(), 403);
    }
}
