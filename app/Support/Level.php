<?php

namespace App\Support;

/** Öğrenci seviyesi: toplam XP'den türetilir (veritabanında tutulmaz). Seviye L, 40·(L−1)² XP'de başlar. */
class Level
{
    private const TITLES = [[1, 'Çaylak', '🐣'], [3, 'Kaşif', '🧭'], [5, 'Kodcu', '💻'], [7, 'Usta', '🛠️'], [10, 'Efsane', '🏆'], [14, 'Süper Kahraman', '🦸']];

    /** @return array{level:int,title:string,icon:string,xp:int,start:int,next:int,pct:int,remaining:int} */
    public static function forXp(int $xp): array
    {
        $xp = max(0, $xp);
        $level = (int) floor(sqrt($xp / 40)) + 1;
        $start = 40 * ($level - 1) ** 2;
        $next = 40 * $level ** 2;
        [$title, $icon] = self::band($level);

        return [
            'level' => $level, 'title' => $title, 'icon' => $icon, 'xp' => $xp, 'start' => $start, 'next' => $next,
            'pct' => (int) min(100, round(($xp - $start) / ($next - $start) * 100)), 'remaining' => $next - $xp,
        ];
    }

    private static function band(int $level): array
    {
        $found = self::TITLES[0];
        foreach (self::TITLES as $t) {
            if ($level >= $t[0]) {
                $found = $t;
            }
        }

        return [$found[1], $found[2]];
    }
}
