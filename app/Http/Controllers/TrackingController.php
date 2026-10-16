<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentActivityLog;
use App\Support\StaffScope;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    private const ACTIVE_SECONDS = 120;

    public function index(Request $request)
    {
        return view('tracking.index', ['classes' => StaffScope::classes($request->user())->get()]);
    }

    public function data(Request $request)
    {
        $students = StaffScope::students($request->user())->with(['user', 'schoolClass'])
            ->when($request->filled('class'), fn ($q) => $q->where('school_class_id', $request->integer('class')))
            ->where('last_seen_at', '>=', now()->subSeconds(self::ACTIVE_SECONDS))
            ->orderByDesc('last_seen_at')->get();

        return response()->json($students->map(fn (Student $s) => [
            'id' => $s->id, 'name' => $s->user->name, 'class' => $s->schoolClass?->label, 'path' => $s->last_path,
            'seconds_ago' => (int) $s->last_seen_at->diffInSeconds(now()), 'history_url' => route('tracking.student', $s),
        ]));
    }

    public function student(Request $request, Student $student)
    {
        StaffScope::authorizeStudent($request->user(), $student);
        $logs = StudentActivityLog::where('student_id', $student->id)->latest('created_at')->limit(100)->get();

        return view('tracking.student', ['student' => $student->load('user', 'schoolClass'), 'logs' => $logs]);
    }

    public function forceLogoutStudent(Request $request, Student $student)
    {
        StaffScope::authorizeStudent($request->user(), $student);
        $student->user->forceFill(['force_logout_at' => now()])->save();

        return back()->with('ok', $student->user->name.' oturumu kapatıldı.');
    }

    public function forceLogoutClass(Request $request, int $class)
    {
        $ids = StaffScope::students($request->user())->where('school_class_id', $class)->pluck('user_id');
        abort_if($ids->isEmpty(), 404);
        \App\Models\User::whereIn('id', $ids)->update(['force_logout_at' => now()]);

        return back()->with('ok', $ids->count().' öğrencinin oturumu kapatıldı.');
    }
}
