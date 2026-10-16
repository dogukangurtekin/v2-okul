<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Services\XpService;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public const COMPLETE_XP = 20;

    private function student()
    {
        return auth()->user()->student ?? abort(403);
    }

    public function index()
    {
        $student = $this->student();
        $courses = Course::visibleToStudent($student)->withCount('slides')->orderBy('sort_order')->get();
        $progress = CourseProgress::where('student_id', $student->id)->get()->keyBy('course_id');

        return view('student.courses', compact('courses', 'progress'));
    }

    public function show(Course $course)
    {
        $student = $this->student();
        abort_unless(Course::visibleToStudent($student)->whereKey($course->id)->exists(), 404);
        $progress = CourseProgress::firstOrCreate(['student_id' => $student->id, 'course_id' => $course->id]);

        return view('courses.player', ['course' => $course->load('slides'), 'progress' => $progress]);
    }

    public function saveProgress(Request $request, Course $course)
    {
        $student = $this->student();
        $index = $request->validate(['slide_index' => ['required', 'integer', 'min:0']])['slide_index'];
        CourseProgress::updateOrCreate(
            ['student_id' => $student->id, 'course_id' => $course->id],
            ['slide_index' => min($index, max(0, $course->slides()->count() - 1))],
        );

        return response()->json(['ok' => true]);
    }

    public function complete(Course $course, XpService $xp)
    {
        $student = $this->student();
        $progress = CourseProgress::firstOrCreate(['student_id' => $student->id, 'course_id' => $course->id]);

        // XP yalnizca ilk tamamlamada verilir (idempotent).
        if (! $progress->completed_at) {
            $progress->update(['completed_at' => now(), 'xp_awarded' => self::COMPLETE_XP]);
            $xp->award($student, self::COMPLETE_XP, 'Ders tamamlandı: '.$course->name, $course);
        }

        return redirect()->route('student.courses')->with('ok', 'Ders tamamlandı! +'.self::COMPLETE_XP.' XP 🎉')->with('celebrate', true);
    }
}
