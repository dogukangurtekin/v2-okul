<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\Student;
use App\Models\XpEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * XP icin tek giris noktasi. Her degisiklik defterde kayit olusturur.
 *  - xp_balance: harcanabilir XP (avatar alimi dusurur)
 *  - xp_total:   kazanilan toplam XP (yalniz artar; rozet ve siralama bunu kullanir)
 */
class XpService
{
    public function award(Student $student, int $delta, string $reason, ?Model $source = null, ?int $grantedBy = null): XpEntry
    {
        return DB::transaction(function () use ($student, $delta, $reason, $source, $grantedBy) {
            $entry = $this->record($student, $delta, $reason, $source, $grantedBy);
            $locked = Student::query()->lockForUpdate()->findOrFail($student->id);
            $locked->xp_balance = max(0, $locked->xp_balance + $delta);
            if ($delta > 0) {
                $locked->xp_total += $delta;
            }
            $locked->save();
            $student->xp_balance = $locked->xp_balance;
            $student->xp_total = $locked->xp_total;

            if ($delta > 0) {
                $this->syncBadges($locked);
            }

            return $entry;
        });
    }

    /** Harcama: toplam XP'yi etkilemez. Yetersiz bakiyede false doner. */
    public function spend(Student $student, int $amount, string $reason, ?Model $source = null): bool
    {
        return DB::transaction(function () use ($student, $amount, $reason, $source) {
            $locked = Student::query()->lockForUpdate()->findOrFail($student->id);
            if ($amount < 0 || $locked->xp_balance < $amount) {
                return false;
            }
            $this->record($locked, -$amount, $reason, $source, null);
            $locked->decrement('xp_balance', $amount);
            $student->xp_balance = $locked->xp_balance;

            return true;
        });
    }

    public function syncBadges(Student $student): void
    {
        $owned = $student->badges()->pluck('badges.id');
        $earned = Badge::where('xp_threshold', '<=', $student->xp_total)->whereNotIn('id', $owned)->pluck('id');
        if ($earned->isNotEmpty()) {
            $student->badges()->attach($earned->mapWithKeys(fn ($id) => [$id => ['awarded_at' => now()]])->all());
        }
    }

    private function record(Student $student, int $delta, string $reason, ?Model $source, ?int $grantedBy): XpEntry
    {
        return XpEntry::create([
            'student_id' => $student->id,
            'delta' => $delta,
            'reason' => $reason,
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
            'granted_by' => $grantedBy,
        ]);
    }
}
