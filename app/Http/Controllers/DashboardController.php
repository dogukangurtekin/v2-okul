<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole('student')) {
            $student = $user->student?->load('schoolClass', 'avatar', 'badges');
            $rank = $student && $student->school_class_id
                ? Student::where('school_class_id', $student->school_class_id)->where('xp_total', '>', $student->xp_total)->count() + 1
                : null;

            return view('dashboard.student', compact('student', 'rank'));
        }

        return view('dashboard.staff', [
            'stats' => [
                'Öğrenci' => Student::count(),
                'Öğretmen' => Teacher::count(),
                'Sınıf' => SchoolClass::count(),
            ],
        ]);
    }
}
