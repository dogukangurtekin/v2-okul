<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Services\XpService;

class AssignmentController extends Controller
{
    private function student()
    {
        return auth()->user()->student ?? abort(403);
    }

    private function visible($student)
    {
        return Assignment::whereHas('classes', fn ($q) => $q->where('school_classes.id', $student->school_class_id));
    }

    public function index()
    {
        $student = $this->student();
        $assignments = $this->visible($student)->with('course')->orderByRaw('due_date IS NULL, due_date')->get();
        $subs = AssignmentSubmission::where('student_id', $student->id)->get()->keyBy('assignment_id');

        return view('student.assignments', compact('assignments', 'subs'));
    }

    public function open(Assignment $assignment)
    {
        $student = $this->student();
        abort_unless($this->visible($student)->whereKey($assignment->id)->exists(), 404);
        $sub = AssignmentSubmission::firstOrCreate(['assignment_id' => $assignment->id, 'student_id' => $student->id], ['started_at' => now()]);

        if ($assignment->type === 'course') {
            return redirect()->route('student.courses.show', $assignment->course_id);
        }

        return redirect()->route('games.play', $assignment->game_slug);
    }

    public function complete(Assignment $assignment, XpService $xp)
    {
        $student = $this->student();
        abort_unless($this->visible($student)->whereKey($assignment->id)->exists(), 404);
        $sub = AssignmentSubmission::firstOrCreate(['assignment_id' => $assignment->id, 'student_id' => $student->id], ['started_at' => now()]);

        if (! $sub->completed_at) {
            $sub->update([
                'completed_at' => now(),
                'xp_awarded' => $assignment->xp_reward,
                'completion_seconds' => $sub->started_at ? (int) $sub->started_at->diffInSeconds(now()) : 0,
            ]);
            $xp->award($student, $assignment->xp_reward, 'Ödev tamamlandı: '.$assignment->title, $assignment);
        }

        return redirect()->route('student.assignments')->with('ok', 'Ödev tamamlandı! 🎉')->with('celebrate', true);
    }
}
