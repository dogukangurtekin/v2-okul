<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function manage(User $user, Course $course): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('teacher')
            && ($course->created_by === $user->id || ($course->teacher_id && $course->teacher_id === $user->teacher?->id));
    }
}
