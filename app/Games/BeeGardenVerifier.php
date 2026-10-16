<?php

namespace App\Games;

/**
 * Arı Bahçesi (CSS/Flexbox): öğrencinin #garden kuralı sunucuda ayrıştırılıp FlexModel ile yerleşimi hesaplanır,
 * referans çözümün yerleşimiyle karşılaştırılır. Aynı sonuca götüren farklı CSS'ler (örn. center / space-around) kabul edilir.
 */
class BeeGardenVerifier implements GameVerifier
{
    private const MAX_BYTES = 4000;

    private function level(int $n): ?array
    {
        return config('game_levels.bee-garden-runner')[$n - 1] ?? null;
    }

    /** @return list<array{0:string,1:string,2:bool}> */
    private function referenceDecls(array $level): array
    {
        $d = [['display', 'flex', false]];
        foreach ($level['solution'] as $p => $v) {
            $d[] = [$p, $v, false];
        }

        return $d;
    }

    /** @return array{error:?string, decls:list<array>} */
    public function parse(string $css): array
    {
        $css = preg_replace('#/\*.*?\*/#s', '', $css);
        if (str_contains($css, '@')) {
            return ['error' => '@-kuralları (@media, @import...) bu atölyede kullanılmaz.', 'decls' => []];
        }
        $decls = [];
        $foreign = [];
        if (preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $m, PREG_SET_ORDER)) {
            foreach ($m as $rule) {
                $selectors = array_map(fn ($s) => preg_replace('/\s+/', ' ', strtolower(trim($s))), explode(',', $rule[1]));
                $body = [];
                foreach (explode(';', $rule[2]) as $decl) {
                    if (str_contains($decl, ':')) {
                        [$p, $v] = array_map('trim', explode(':', $decl, 2));
                        $important = (bool) preg_match('/!\s*important\s*$/i', $v);
                        $v = strtolower(trim(preg_replace('/!\s*important\s*$/i', '', $v)));
                        if ($p !== '' && $v !== '') {
                            $body[] = [strtolower($p), preg_replace('/\s+/', ' ', $v), $important];
                        }
                    }
                }
                foreach ($selectors as $sel) {
                    if ($sel === '#garden') {
                        array_push($decls, ...$body);
                    } elseif ($body) {
                        $foreign[$sel] = true;
                    }
                }
            }
        }
        if ($foreign) {
            return ['error' => 'Yalnızca #garden için kural yazın (bulundu: '.implode(', ', array_slice(array_keys($foreign), 0, 3)).').', 'decls' => []];
        }

        return ['error' => null, 'decls' => $decls];
    }

    public function verify(int $level, array $payload): array
    {
        $def = $this->level($level);
        $css = $payload['css'] ?? null;
        if (! $def || ! is_string($css) || strlen($css) > self::MAX_BYTES) {
            return ['ok' => false, 'messages' => ['Geçersiz CSS veya çok büyük.'], 'score' => 0];
        }
        $p = $this->parse($css);
        if ($p['error']) {
            return ['ok' => false, 'messages' => [$p['error']], 'score' => 0];
        }
        $mine = FlexModel::layout($p['decls'], $def['bees']);
        if ($mine['error']) {
            return ['ok' => false, 'messages' => [$mine['error']], 'score' => 0];
        }
        $want = FlexModel::layout($this->referenceDecls($def), $def['bees'])['positions'];

        $on = 0;
        foreach ($want as $i => $w) {
            $g = $mine['positions'][$i];
            $on += (abs($g[0] - $w[0]) < 0.5 && abs($g[1] - $w[1]) < 0.5) ? 1 : 0;
        }
        if ($on !== count($want)) {
            return ['ok' => false, 'messages' => [$def['bees'] > 1 ? "{$on}/{$def['bees']} arı çiçeğin üstünde. Dizilime ve yöne dikkat et." : 'Arı henüz çiçeğe ulaşmadı.', ...($def['hint'] ? ['İpucu: '.$def['hint']] : [])], 'score' => 0];
        }

        return ['ok' => true, 'messages' => [], 'score' => 10, 'explain' => $def['explain'] ?? null];
    }

    public function publicLevels(array $levels): array
    {
        return array_map(function ($l) {
            $flowers = FlexModel::layout($this->referenceDecls($l), $l['bees'])['positions'];

            return ['title' => $l['title'], 'task' => $l['task'], 'bees' => $l['bees'], 'flowers' => $flowers, 'css' => "#garden {\n  display: flex;\n  \n}\n"];
        }, $levels);
    }
}
