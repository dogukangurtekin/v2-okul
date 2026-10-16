<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\Badge;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\LiveSession;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentActivityLog;
use App\Models\SupportRequest;
use App\Models\Teacher;
use App\Models\XpEntry;
use App\Services\GameAccessService;
use App\Support\Level;
use App\Support\StaffScope;

class DashboardController extends Controller
{
    public function index(GameAccessService $games)
    {
        $user = auth()->user();

        return $user->hasRole('student') ? $this->student($user, $games) : $this->staff($user);
    }

    private function student($user, GameAccessService $games)
    {
        $student = $user->student?->load('schoolClass', 'avatar', 'badges');
        if (! $student) {
            return view('dashboard.student', ['student' => null, 'lv' => Level::forXp(0), 'rank' => null, 'pending' => collect(), 'resume' => null, 'badges' => collect(), 'earned' => [], 'live' => null, 'stats' => [], 'announcements' => collect()]);
        }

        $rank = $student->school_class_id
            ? Student::where('school_class_id', $student->school_class_id)->where('xp_total', '>', $student->xp_total)->count() + 1 : null;

        $assignments = $student->school_class_id
            ? Assignment::with('course')->whereHas('classes', fn ($q) => $q->where('school_classes.id', $student->school_class_id))->get() : collect();
        $done = AssignmentSubmission::where('student_id', $student->id)->whereNotNull('completed_at')->pluck('assignment_id');
        $pending = $assignments->reject(fn ($a) => $done->contains($a->id))->sortBy(fn ($a) => $a->due_date?->timestamp ?? PHP_INT_MAX)->values();

        $visibleCourses = Course::visibleToStudent($student)->pluck('id');
        $progress = CourseProgress::with('course.slides')->where('student_id', $student->id)->whereIn('course_id', $visibleCourses)->get();
        $resume = $progress->whereNull('completed_at')->sortByDesc('updated_at')->first();
        $resumeData = $resume ? [
            'course' => $resume->course, 'total' => max(1, $resume->course->slides->count()), 'at' => min($resume->slide_index + 1, max(1, $resume->course->slides->count())),
        ] : null;

        $live = $student->school_class_id
            ? LiveSession::where('school_class_id', $student->school_class_id)->where('status', '!=', 'finished')->latest()->first() : null;

        $allBadges = Badge::orderBy('xp_threshold')->get();
        $earned = $student->badges->pluck('id')->all();

        return view('dashboard.student', [
            'student' => $student, 'lv' => Level::forXp($student->xp_total), 'rank' => $rank,
            'classSize' => $student->school_class_id ? Student::where('school_class_id', $student->school_class_id)->count() : 0,
            'pending' => $pending, 'resume' => $resumeData, 'badges' => $allBadges, 'earned' => $earned, 'live' => $live,
            'stats' => [
                'courses_done' => $progress->whereNotNull('completed_at')->count(), 'courses_total' => $visibleCourses->count(),
                'games' => count($games->allowedSlugs($student)),
            ],
            'announcements' => Announcement::visibleTo($user)->limit(2)->get(),
        ]);
    }

    private function staff($user)
    {
        $students = StaffScope::students($user);
        $classes = StaffScope::classes($user);
        $studentIds = (clone $students)->pluck('id');
        $isAdmin = $user->hasRole('admin');

        // Son 7 gün öğrenci etkinliği (sayfa görüntüleme)
        $days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->startOfDay());
        $counts = StudentActivityLog::whereIn('student_id', $studentIds)->where('created_at', '>=', $days->first())
            ->get(['created_at'])->groupBy(fn ($l) => $l->created_at->toDateString())->map->count();
        $chart = $days->map(fn ($d) => ['label' => $d->locale('tr')->isoFormat('dd'), 'n' => (int) ($counts[$d->toDateString()] ?? 0), 'today' => $d->isToday()])->all();

        $classIds = (clone $classes)->whereHas('students')->pluck('id');
        $takenToday = $classIds->isEmpty() ? 0 : SchoolClass::whereIn('id', $classIds)->get()
            ->filter(fn ($c) => Attendance::whereDate('date', today())->whereIn('student_id', $c->students()->select('id'))->exists())->count();

        return view('dashboard.staff', [
            'name' => explode(' ', trim($user->name))[0],
            'stats' => [
                'students' => $studentIds->count(), 'classes' => (clone $classes)->count(),
                'courses' => $isAdmin ? Course::count() : Course::where('created_by', $user->id)->orWhere('teacher_id', $user->teacher?->id)->count(),
                'active' => (clone $students)->where('last_seen_at', '>=', now()->subMinutes(2))->count(),
                'teachers' => $isAdmin ? Teacher::count() : null,
            ],
            'chart' => $chart, 'chartMax' => max(1, collect($chart)->max('n')),
            'attendance' => ['taken' => $takenToday, 'total' => $classIds->count()],
            'support' => $isAdmin ? SupportRequest::whereNull('read_at')->whereNull('archived_at')->count() : 0,
            'liveCount' => LiveSession::where('status', '!=', 'finished')->when(! $isAdmin, fn ($q) => $q->where('host_user_id', $user->id))->count(),
            'top' => (clone $students)->with('user', 'avatar', 'schoolClass')->orderByDesc('xp_total')->limit(5)->get(),
            'recent' => XpEntry::with('student.user')->whereIn('student_id', $studentIds)->latest()->limit(6)->get(),
            'announcements' => Announcement::visibleTo($user)->limit(2)->get(),
        ]);
    }
}
