<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\GameProgress;
use App\Models\LiveParticipant;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Toplu sorgularla (N+1 yok) ogrenci basari gostergeleri. */
class AnalyticsService
{
    public function __construct(private AttendanceService $attendance, private GradeService $grades) {}

    /** @param Collection<int,Student> $students @return array<int,array<string,mixed>> */
    public function forStudents(Collection $students): array
    {
        $ids = $students->pluck('id');
        $classIds = $students->pluck('school_class_id')->filter()->unique();

        $assignmentsPerClass = DB::table('assignment_school_class as ac')->join('assignments as a', 'a.id', '=', 'ac.assignment_id')
            ->whereNull('a.deleted_at')->whereIn('ac.school_class_id', $classIds)->groupBy('ac.school_class_id')
            ->pluck(DB::raw('count(*)'), 'ac.school_class_id');
        $coursesPerClass = DB::table('course_school_class as cc')->join('courses as c', 'c.id', '=', 'cc.course_id')
            ->where('c.is_active', true)->whereIn('cc.school_class_id', $classIds)->groupBy('cc.school_class_id')
            ->pluck(DB::raw('count(*)'), 'cc.school_class_id');

        $doneAssignments = AssignmentSubmission::whereIn('student_id', $ids)->whereNotNull('completed_at')
            ->whereHas('assignment')->groupBy('student_id')->pluck(DB::raw('count(*)'), 'student_id');
        $doneCourses = CourseProgress::whereIn('student_id', $ids)->whereNotNull('completed_at')->groupBy('student_id')->pluck(DB::raw('count(*)'), 'student_id');
        $levels = GameProgress::whereIn('student_id', $ids)->groupBy('student_id')->pluck(DB::raw('count(*)'), 'student_id');
        $wpm = LiveParticipant::whereIn('student_id', $ids)->where('wpm', '>', 0)->groupBy('student_id')->pluck(DB::raw('max(wpm)'), 'student_id');
        $sessions = LiveParticipant::whereIn('student_id', $ids)->groupBy('student_id')->pluck(DB::raw('count(*)'), 'student_id');

        $att = $this->attendance->summary($ids);
        $avg = $this->grades->averages($ids);

        $out = [];
        foreach ($students as $s) {
            $aTotal = (int) ($assignmentsPerClass[$s->school_class_id] ?? 0);
            $cTotal = (int) ($coursesPerClass[$s->school_class_id] ?? 0);
            $aDone = min((int) ($doneAssignments[$s->id] ?? 0), $aTotal ?: PHP_INT_MAX);
            $cDone = min((int) ($doneCourses[$s->id] ?? 0), $cTotal ?: PHP_INT_MAX);
            $rates = array_filter([$aTotal ? $aDone / $aTotal : null, $cTotal ? $cDone / $cTotal : null], fn ($v) => $v !== null);

            $out[$s->id] = [
                'xp_total' => $s->xp_total, 'xp_balance' => $s->xp_balance,
                'assignments_done' => $aDone, 'assignments_total' => $aTotal,
                'courses_done' => $cDone, 'courses_total' => $cTotal,
                'game_levels' => (int) ($levels[$s->id] ?? 0),
                'best_wpm' => (int) ($wpm[$s->id] ?? 0),
                'live_sessions' => (int) ($sessions[$s->id] ?? 0),
                'attendance_rate' => $att[$s->id]['rate'], 'absent_days' => $att[$s->id]['absent'], 'grade_avg' => $avg[$s->id],
                'minutes' => intdiv((int) $s->total_seconds, 60),
                'last_seen' => $s->last_seen_at,
                'score' => $rates ? (int) round(array_sum($rates) / count($rates) * 100) : null,
            ];
        }

        return $out;
    }
}
