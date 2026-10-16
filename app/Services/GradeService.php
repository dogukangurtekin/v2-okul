<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\Student;
use Illuminate\Support\Collection;

class GradeService
{
    public function __construct(private XpService $xp) {}

    /** Ornek: "2026-2027 1. Dönem" (Eylül–Ocak 1., Şubat–Ağustos 2. dönem). */
    public static function currentTerm(?\Carbon\Carbon $at = null): string
    {
        $at ??= now();
        $m = (int) $at->format('n');
        $startYear = $m >= 9 ? (int) $at->format('Y') : (int) $at->format('Y') - 1;
        $term = ($m >= 9 || $m === 1) ? 1 : 2;

        return $startYear.'-'.($startYear + 1).' '.$term.'. Dönem';
    }

    public function weight(string $type): int
    {
        return (int) (config("school.exam_types.$type.weight") ?? 1);
    }

    /** Agirlikli ortalama (0-100) veya null. */
    public function weightedAverage(Collection $grades): ?float
    {
        $w = $grades->sum(fn ($g) => $this->weight($g->exam_type));

        return $w > 0 ? round($grades->sum(fn ($g) => $g->score * $this->weight($g->exam_type)) / $w, 1) : null;
    }

    /** @return array<int,?float> ogrenci => agirlikli ortalama */
    public function averages(Collection $studentIds, ?string $term = null): array
    {
        $by = Grade::whereIn('student_id', $studentIds)->when($term, fn ($q) => $q->where('term', $term))->get()->groupBy('student_id');
        $out = [];
        foreach ($studentIds as $id) {
            $out[$id] = isset($by[$id]) ? $this->weightedAverage($by[$id]) : null;
        }

        return $out;
    }

    public function create(array $data): Grade
    {
        $grade = Grade::create($data);
        $this->syncXp($grade, 0);

        return $grade;
    }

    public function update(Grade $grade, array $data): Grade
    {
        $before = (int) round($grade->score);
        $grade->update($data);
        $this->syncXp($grade, $before);

        return $grade;
    }

    /** Yalniz school.grade_xp aciksa: not puani kadar XP (guncellemede fark). Silmede XP geri alinmaz (v1 ile ayni). */
    private function syncXp(Grade $grade, int $beforeRounded): void
    {
        if (! config('school.grade_xp')) {
            return;
        }
        $delta = (int) round($grade->score) - $beforeRounded;
        if ($delta !== 0) {
            $this->xp->award(Student::findOrFail($grade->student_id), $delta, 'Not: '.($grade->title ?: config("school.exam_types.{$grade->exam_type}.label")), $grade, $grade->recorded_by);
        }
    }
}
