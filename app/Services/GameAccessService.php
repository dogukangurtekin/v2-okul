<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

/** v1 StudentGameAccessService karsiligi: sinif ogretmeninin actigi oyunlar + sinifa verilen oyun odevleri. */
class GameAccessService
{
    /** @return list<string> */
    public function allowedSlugs(Student $student): array
    {
        $teacherId = $student->schoolClass?->teacher_id;
        $unlocked = $teacherId ? DB::table('teacher_games')->where('teacher_id', $teacherId)->pluck('game_slug')->all() : [];

        $assigned = $student->school_class_id
            ? Assignment::where('type', 'game')->whereHas('classes', fn ($q) => $q->where('school_classes.id', $student->school_class_id))->pluck('game_slug')->all()
            : [];

        return array_values(array_intersect(array_unique([...$unlocked, ...$assigned]), array_keys(config('games'))));
    }

    public function canPlay(Student $student, string $slug): bool
    {
        return in_array($slug, $this->allowedSlugs($student), true) || $this->inLiveCompetition($student, $slug);
    }

    /** Canli oyun yarismasina katilan ogrenci, o oyunu yarisma suresince oynayabilir. */
    private function inLiveCompetition(Student $student, string $slug): bool
    {
        return \App\Models\LiveParticipant::where('student_id', $student->id)
            ->whereHas('session', fn ($q) => $q->where(['type' => 'competition', 'status' => 'live'])->where('config->game_slug', $slug))
            ->exists();
    }
}
