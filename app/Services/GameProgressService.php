<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\GameProgress;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class GameProgressService
{
    public function __construct(private XpService $xp) {}

    /**
     * Seviye sonucunu kaydeder. Seviye N yalniz N-1 bitmisse kabul edilir; XP seviye basina bir kez verilir.
     *
     * @return array{xp:int, repeat:bool}|null null = seviye kabul edilmedi
     */
    public function record(Student $student, string $slug, int $level, int $seconds, int $score): ?array
    {
        $cfg = config("runners.$slug");
        if (! $cfg || $level < 1 || $level > ($cfg['levels'] ?? 1)) {
            return null;
        }
        if ($level > 1 && ! GameProgress::where(['student_id' => $student->id, 'game_slug' => $slug, 'level' => $level - 1])->exists()) {
            return null;
        }

        return DB::transaction(function () use ($student, $slug, $level, $seconds, $score, $cfg) {
            $row = GameProgress::where(['student_id' => $student->id, 'game_slug' => $slug, 'level' => $level])->lockForUpdate()->first();

            if ($row) {
                $row->update([
                    'plays' => $row->plays + 1,
                    'best_seconds' => min($row->best_seconds ?? PHP_INT_MAX, $seconds),
                    'best_score' => max($row->best_score, $score),
                ]);
                $result = ['xp' => 0, 'repeat' => true];
            } else {
                $gain = (int) ($cfg['xp'] ?? 10);
                GameProgress::create([
                    'student_id' => $student->id, 'game_slug' => $slug, 'level' => $level,
                    'best_seconds' => $seconds, 'best_score' => $score, 'xp_awarded' => $gain,
                ]);
                $this->xp->award($student, $gain, "Oyun: ".config("games.$slug")." seviye $level");
                $result = ['xp' => $gain, 'repeat' => false];
            }

            $this->completeAssignments($student, $slug);

            return $result;
        });
    }

    /** Sinifa verilmis oyun odevinin seviye araligi tamamen bittiyse odevi tamamla. */
    private function completeAssignments(Student $student, string $slug): void
    {
        if (! $student->school_class_id) {
            return;
        }
        $done = GameProgress::where(['student_id' => $student->id, 'game_slug' => $slug])->pluck('level')->all();
        $max = config("runners.$slug.levels", 1);

        Assignment::where(['type' => 'game', 'game_slug' => $slug])
            ->whereHas('classes', fn ($q) => $q->where('school_classes.id', $student->school_class_id))
            ->get()->each(function (Assignment $a) use ($student, $done, $max) {
                $needed = range($a->level_from ?? 1, min($a->level_to ?? $max, $max));
                if (array_diff($needed, $done)) {
                    return;
                }
                $sub = AssignmentSubmission::firstOrCreate(['assignment_id' => $a->id, 'student_id' => $student->id], ['started_at' => now()]);
                if (! $sub->completed_at) {
                    $sub->update(['completed_at' => now(), 'xp_awarded' => $a->xp_reward, 'reached_level' => max($needed)]);
                    $this->xp->award($student, $a->xp_reward, 'Ödev tamamlandı: '.$a->title, $a);
                }
            });
    }
}
