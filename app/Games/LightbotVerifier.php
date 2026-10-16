<?php

namespace App\Games;

/**
 * Lightbot (2B): robot dogu yonune bakarak baslar; hedef tum 'L' karolarini yakmak.
 * Komutlar: F ileri, L sola don, R saga don, X isik yak, P prosedur cagir (yalniz ana programda).
 * Duvara/kenara carpmak programi basarisiz kilar. Calisma 1000 adimla sinirlidir.
 */
class LightbotVerifier implements GameVerifier
{
    private const MAX_STEPS = 1000;
    private const CMDS = ['F', 'L', 'R', 'X', 'P'];
    private const DIRS = [[1, 0], [0, 1], [-1, 0], [0, -1]]; // dogu, guney, bati, kuzey (y asagi)

    /** @return array{ok:bool, error:?string, lit:int, total:int} */
    public function simulate(array $level, array $main, array $proc): array
    {
        $grid = $level['grid'];
        $h = count($grid);
        $w = strlen($grid[0]);
        $x = $y = 0;
        $lights = [];
        foreach ($grid as $r => $row) {
            foreach (str_split($row) as $c => $ch) {
                if ($ch === 'S') {
                    [$x, $y] = [$c, $r];
                }
                if ($ch === 'L') {
                    $lights["$c,$r"] = false;
                }
            }
        }
        $d = 0;
        $steps = 0;

        // Ana programi prosedurle acarak duz komut listesi yap.
        $flat = [];
        foreach ($main as $cmd) {
            if ($cmd === 'P') {
                array_push($flat, ...$proc);
            } else {
                $flat[] = $cmd;
            }
        }
        foreach ($flat as $cmd) {
            if (++$steps > self::MAX_STEPS) {
                return ['ok' => false, 'error' => 'Program çok uzun sürdü.', 'lit' => 0, 'total' => count($lights)];
            }
            switch ($cmd) {
                case 'L': $d = ($d + 3) % 4; break;
                case 'R': $d = ($d + 1) % 4; break;
                case 'F':
                    $nx = $x + self::DIRS[$d][0];
                    $ny = $y + self::DIRS[$d][1];
                    if ($nx < 0 || $ny < 0 || $nx >= $w || $ny >= $h || $grid[$ny][$nx] === '#') {
                        return ['ok' => false, 'error' => 'Robot duvara çarptı.', 'lit' => count(array_filter($lights)), 'total' => count($lights)];
                    }
                    [$x, $y] = [$nx, $ny];
                    break;
                case 'X':
                    if (isset($lights["$x,$y"])) {
                        $lights["$x,$y"] = true;
                    }
                    break;
            }
        }
        $lit = count(array_filter($lights));

        return ['ok' => $lit === count($lights), 'error' => $lit === count($lights) ? null : 'Bütün ışıklar yanmadı.', 'lit' => $lit, 'total' => count($lights)];
    }

    public function verify(int $level, array $payload): array
    {
        $def = config('game_levels.lightbot-runner')[$level - 1] ?? null;
        $main = $payload['main'] ?? null;
        $proc = $payload['proc'] ?? [];
        if (! $def || ! is_array($main) || ! is_array($proc)) {
            return ['ok' => false, 'messages' => ['Geçersiz program.'], 'score' => 0];
        }
        $main = array_values($main);
        $proc = array_values($proc);
        foreach ([$main, $proc] as $list) {
            foreach ($list as $c) {
                if (! is_string($c) || ! in_array($c, self::CMDS, true)) {
                    return ['ok' => false, 'messages' => ['Geçersiz komut.'], 'score' => 0];
                }
            }
        }
        if (count($main) > $def['main_max'] || count($proc) > $def['proc_max'] || in_array('P', $proc, true) || ($def['proc_max'] === 0 && in_array('P', $main, true))) {
            return ['ok' => false, 'messages' => ['Komut sınırını aştın.'], 'score' => 0];
        }

        $r = $this->simulate($def, $main, $proc);
        if (! $r['ok']) {
            return ['ok' => false, 'messages' => [$r['error'].' ('.$r['lit'].'/'.$r['total'].' ışık)'], 'score' => 0];
        }
        $saved = ($def['main_max'] + $def['proc_max']) - (count($main) + count($proc));

        return ['ok' => true, 'messages' => [], 'score' => max(0, $saved) * 10, 'explain' => null];
    }

    public function publicLevels(array $levels): array
    {
        return array_map(fn ($l) => ['title' => $l['title'], 'grid' => $l['grid'], 'main_max' => $l['main_max'], 'proc_max' => $l['proc_max']], $levels);
    }
}
