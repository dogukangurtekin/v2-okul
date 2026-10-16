<?php

namespace App\Games;

/**
 * Python Editörü görevleri. DİKKAT — sınırlı güvence: Python tarayıcıda (Pyodide) çalışır; sunucu kodu yeniden ÇALIŞTIRMAZ
 * (öğrenci kodunu sunucuda çalıştırmak güvenli değildir). Sunucu, istemcinin bildirdiği çıktıyı beklenenle karşılaştırır
 * ve kodun yapısını denetler (zorunlu yapılar var mı, cevabı doğrudan yazan sabit var mı). Bu, kazara/tembel atlamayı
 * engeller; kararlı bir hile girişimini engellemez. Silent Teacher/Lightbot/Web Atölyesi/Arı Bahçesi gibi tam doğrulama DEĞİLDİR.
 */
class PythonEditorVerifier implements GameVerifier
{
    private const MAX_CODE = 5000;
    private const MAX_OUT = 2000;

    public function verify(int $level, array $payload): array
    {
        $def = config('game_levels.python-editor')[$level - 1] ?? null;
        $code = $payload['code'] ?? null;
        $stdout = $payload['stdout'] ?? null;
        if (! $def || ! is_string($code) || ! is_string($stdout) || strlen($code) > self::MAX_CODE || strlen($stdout) > self::MAX_OUT) {
            return ['ok' => false, 'messages' => ['Geçersiz gönderim.'], 'score' => 0];
        }

        if (SilentTeacherVerifier::normalize($stdout) !== SilentTeacherVerifier::normalize($def['expect'])) {
            return ['ok' => false, 'messages' => ['Programın çıktısı beklenenle aynı değil. Çalıştır ve çıktıyı görevle karşılaştır.', ...($def['hint'] ? ['İpucu: '.$def['hint']] : [])], 'score' => 0];
        }

        $structure = self::blank($code, true);        // yorum + metin içeriği atılmış: "for" kelimesi yorumda/metinde sayılmaz
        $noComments = self::blank($code, false);      // yalnız yorum atılmış: sabit yazma kontrolü metni de görür
        foreach ($def['must'] as $re => $msg) {
            if (! preg_match($re, $structure)) {
                return ['ok' => false, 'messages' => [$msg], 'score' => 0];
            }
        }
        foreach ($def['must_not'] as $re => $msg) {
            if (preg_match($re, $noComments)) {
                return ['ok' => false, 'messages' => [$msg], 'score' => 0];
            }
        }

        return ['ok' => true, 'messages' => [], 'score' => 10, 'explain' => $def['explain'] ?? null];
    }

    public function publicLevels(array $levels): array
    {
        return array_map(fn ($l) => ['title' => $l['title'], 'task' => $l['task'], 'starter' => $l['starter']], $levels);
    }

    /** Yorumları siler; $blankStrings ise metin içeriklerini de boşaltır (tırnaklar kalır). Tek/çift/üçlü tırnaklara saygılı. */
    public static function blank(string $code, bool $blankStrings): string
    {
        $out = '';
        $n = strlen($code);
        for ($i = 0; $i < $n; $i++) {
            $c = $code[$i];
            if ($c === '#') {                                   // satır sonuna kadar yorum
                while ($i < $n && $code[$i] !== "\n") {
                    $i++;
                }
                $out .= "\n";
                continue;
            }
            if ($c === '"' || $c === "'") {
                $triple = substr($code, $i, 3) === str_repeat($c, 3);
                $q = $triple ? str_repeat($c, 3) : $c;
                $out .= $q;
                $i += strlen($q);
                $closed = false;
                while ($i < $n) {
                    if ($code[$i] === '\\' && $i + 1 < $n) {
                        if (! $blankStrings) {
                            $out .= $code[$i].$code[$i + 1];
                        }
                        $i += 2;
                        continue;
                    }
                    if (substr($code, $i, strlen($q)) === $q) {
                        $closed = true;
                        break;
                    }
                    if (! $triple && $code[$i] === "\n") {
                        break;                                  // kapanmamış metin: satırda dur
                    }
                    if (! $blankStrings) {
                        $out .= $code[$i];
                    }
                    $i++;
                }
                if ($closed) {
                    $out .= $q;
                    $i += strlen($q) - 1;
                } else {
                    $i--;                                       // satır sonu / dosya sonu dış döngüde işlensin
                }
                continue;
            }
            $out .= $c;
        }

        return $out;
    }
}
