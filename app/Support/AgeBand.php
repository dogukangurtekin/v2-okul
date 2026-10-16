<?php

namespace App\Support;

use App\Models\Student;

/**
 * Öğrenci arayüzünün yaş bandı (sınıf düzeyinden türetilir):
 *   kid  = 1–4. sınıf  (oyunsu, büyük, sevimli)
 *   mid  = 5–8. sınıf  (daha derli toplu, "atölye")
 *   teen = 9–12. sınıf (koyu, sade, editör havası)
 * ?band=kid|mid|teen adresi görünümü oturum boyunca önizler, ?band=auto sıfırlar (yalnızca görünüm; veriyi etkilemez).
 */
class AgeBand
{
    public const BANDS = ['kid', 'mid', 'teen'];

    public static function forGrade(?int $grade): string
    {
        return match (true) {
            $grade === null || $grade <= 0 => 'mid',
            $grade <= 4 => 'kid',
            $grade <= 8 => 'mid',
            default => 'teen',
        };
    }

    public static function forStudent(?Student $student): string
    {
        return self::forGrade($student?->schoolClass?->grade_level);
    }

    /** Geçerli istek için band (önizleme > öğrencinin sınıfı). */
    public static function current(): string
    {
        $user = auth()->user();
        if (! $user?->hasRole('student')) {
            return 'mid';
        }
        $q = request()->query('band');
        if (is_string($q) && request()->hasSession()) {
            if ($q === 'auto') {
                session()->forget('band_preview');
            } elseif (in_array($q, self::BANDS, true)) {
                session()->put('band_preview', $q);
            }
        }
        $preview = request()->hasSession() ? session('band_preview') : null;

        return in_array($preview, self::BANDS, true) ? $preview : self::forStudent($user->student);
    }
}
