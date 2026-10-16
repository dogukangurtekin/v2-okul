<?php

namespace App\Games;

/**
 * Web Atolyesi: ogrencinin HTML+CSS'i sunucuda ayristirilip gorev kontrollerine gore denetlenir.
 * Kullanici kodu hicbir yerde calistirilmaz veya saklanmaz (JavaScript yok; onizleme sandbox'li iframe'dedir).
 */
class WebLabVerifier implements GameVerifier
{
    private const MAX_BYTES = 20000;

    public function verify(int $level, array $payload): array
    {
        $def = config('game_levels.web-lab-runner')[$level - 1] ?? null;
        $html = $payload['html'] ?? null;
        $css = $payload['css'] ?? '';
        if (! $def || ! is_string($html) || ! is_string($css) || strlen($html) > self::MAX_BYTES || strlen($css) > self::MAX_BYTES) {
            return ['ok' => false, 'messages' => ['Geçersiz veya çok büyük kod.'], 'score' => 0];
        }

        $dom = $this->parseHtml($html);
        // <style> bloklari da CSS sayilir (ogrenci HTML icine yazabilir).
        foreach ($dom->getElementsByTagName('style') as $st) {
            $css .= "\n".$st->textContent;
        }
        $rules = $this->parseCss($css);

        $missing = [];
        foreach ($def['checks'] as $check) {
            if (! $this->passes($check, $dom, $rules)) {
                $missing[] = $check['msg'];
            }
        }

        return $missing ? ['ok' => false, 'messages' => $missing, 'score' => 0] : ['ok' => true, 'messages' => [], 'score' => 10, 'explain' => $def['explain'] ?? null];
    }

    public function publicLevels(array $levels): array
    {
        return array_map(fn ($l) => ['title' => $l['title'], 'task' => $l['task'], 'html' => $l['html'], 'css' => $l['css'] ?? ''], $levels);
    }

    private function parseHtml(string $html): \DOMDocument
    {
        $dom = new \DOMDocument;
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        return $dom;
    }

    /** @return array<string,array<string,string>> secici => ozellik => deger (sonraki kural oncekini ezer) */
    public function parseCss(string $css): array
    {
        $css = preg_replace('#/\*.*?\*/#s', '', $css);
        $rules = [];
        if (preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $m, PREG_SET_ORDER)) {
            foreach ($m as $rule) {
                foreach (explode(',', $rule[1]) as $sel) {
                    $sel = preg_replace('/\s+/', ' ', strtolower(trim($sel)));
                    if ($sel === '' || $sel[0] === '@') {
                        continue;
                    }
                    foreach (explode(';', $rule[2]) as $decl) {
                        if (! str_contains($decl, ':')) {
                            continue;
                        }
                        [$prop, $val] = array_map('trim', explode(':', $decl, 2));
                        $val = strtolower(trim(str_ireplace('!important', '', $val)));
                        if ($prop !== '' && $val !== '') {
                            $rules[$sel][strtolower($prop)] = $val;
                        }
                    }
                }
            }
        }

        return $rules;
    }

    private function passes(array $c, \DOMDocument $dom, array $rules): bool
    {
        switch ($c['type']) {
            case 'tag':
                $found = 0;
                foreach ($dom->getElementsByTagName($c['tag']) as $el) {
                    if (isset($c['text']) && ! str_contains(mb_strtolower($el->textContent), mb_strtolower($c['text']))) {
                        continue;
                    }
                    if (isset($c['min_text']) && mb_strlen(trim($el->textContent)) < $c['min_text']) {
                        continue;
                    }
                    $found++;
                }

                return $found >= ($c['min'] ?? 1);
            case 'attr':
                foreach ($dom->getElementsByTagName($c['tag']) as $el) {
                    $v = trim($el->getAttribute($c['attr']));
                    if ($v !== '' && (! isset($c['prefix']) || str_starts_with(strtolower($v), $c['prefix']))) {
                        return true;
                    }
                }

                return false;
            case 'class':
                foreach ((new \DOMXPath($dom))->query('//*[@class]') as $el) {
                    if (in_array($c['class'], preg_split('/\s+/', trim($el->getAttribute('class'))), true)) {
                        return true;
                    }
                }

                return false;
            case 'css':
                $val = $rules[strtolower($c['selector'])][$c['prop']] ?? null;
                if ($val === null) {
                    return false;
                }
                if (isset($c['values'])) {
                    $norm = str_replace(' ', '', $val);

                    return in_array($norm, array_map(fn ($v) => str_replace(' ', '', strtolower($v)), $c['values']), true);
                }

                return true;
        }

        return false;
    }
}
