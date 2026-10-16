<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Bir sinifin bir gunluk yoklamasini toplu kaydeder (upsert). Sinif disindaki ogrenci kimlikleri yok sayilir.
     *
     * @param  array<int,array{status:string,note?:?string}>  $rows  student_id => satir
     */
    public function saveSheet(Collection $classStudentIds, string $date, ?int $courseId, array $rows, User $by): int
    {
        $valid = array_keys(config('school.attendance_statuses'));
        $n = 0;
        DB::transaction(function () use ($classStudentIds, $date, $courseId, $rows, $by, $valid, &$n) {
            foreach ($rows as $studentId => $row) {
                if (! $classStudentIds->contains((int) $studentId) || ! in_array($row['status'] ?? null, $valid, true)) {
                    continue;
                }
                Attendance::updateOrCreate(
                    ['student_id' => (int) $studentId, 'date' => $date, 'course_key' => $courseId ?? 0],
                    ['course_id' => $courseId, 'status' => $row['status'], 'note' => $row['note'] ?? null, 'recorded_by' => $by->id],
                );
                $n++;
            }
        });

        return $n;
    }

    /** @return array<int,array{present:int,late:int,excused:int,absent:int,total:int,rate:?int}> ogrenci basina ozet (genel gunluk + ders yoklamalari birlikte) */
    public function summary(Collection $studentIds, ?string $from = null, ?string $to = null): array
    {
        $rows = Attendance::whereIn('student_id', $studentIds)
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))->when($to, fn ($q) => $q->whereDate('date', '<=', $to))
            ->groupBy('student_id', 'status')->select('student_id', 'status', DB::raw('count(*) as c'))->get();

        $out = [];
        foreach ($studentIds as $id) {
            $out[$id] = ['present' => 0, 'late' => 0, 'excused' => 0, 'absent' => 0, 'total' => 0, 'rate' => null];
        }
        foreach ($rows as $r) {
            $out[$r->student_id][$r->status] = (int) $r->c;
            $out[$r->student_id]['total'] += (int) $r->c;
        }
        foreach ($out as &$o) {
            // Devam orani: izinli sayilmaz (payda disi); gec gelen devam sayilir.
            $counted = $o['total'] - $o['excused'];
            $o['rate'] = $counted > 0 ? (int) round(($o['present'] + $o['late']) / $counted * 100) : null;
        }

        return $out;
    }

    /** Bugun yoklamasi eksik sinifi olan ogretmenlerin kullanici kimlikleri. */
    public function teachersMissingToday(): Collection
    {
        $today = today()->toDateString();

        return \App\Models\SchoolClass::with('teacher')->whereNotNull('teacher_id')->whereHas('students')->get()
            ->filter(fn ($c) => ! Attendance::whereDate('date', $today)->whereIn('student_id', $c->students()->select('id'))->exists())
            ->map(fn ($c) => $c->teacher->user_id)->unique()->values();
    }
}
