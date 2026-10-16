<?php

namespace App\Games;

/** Python kodunun ciktisini tahmin etme: beklenen cikti yalniz sunucuda. */
class SilentTeacherVerifier implements GameVerifier
{
    private function levels(): array
    {
        return config('game_levels.silent-teacher-runner');
    }

    public static function normalize(string $s): string
    {
        $s = str_replace(["\r\n", "\r"], "\n", $s);
        if (class_exists(\Normalizer::class)) {
            $s = \Normalizer::normalize($s, \Normalizer::FORM_C) ?: $s;
        }
        $lines = array_map('rtrim', explode("\n", $s));

        return trim(implode("\n", $lines));
    }

    public function verify(int $level, array $payload): array
    {
        $def = $this->levels()[$level - 1] ?? null;
        $answer = $payload['answer'] ?? null;
        if (! $def || ! is_string($answer) || mb_strlen($answer) > 500) {
            return ['ok' => false, 'messages' => ['Geçersiz cevap.'], 'score' => 0];
        }
        if (self::normalize($answer) !== self::normalize($def['output'])) {
            return ['ok' => false, 'messages' => [$def['hint'] ?? 'Çıktı doğru değil, kodu satır satır tekrar izle.'], 'score' => 0];
        }

        return ['ok' => true, 'messages' => [], 'score' => 10, 'explain' => $def['explain'] ?? null];
    }

    public function publicLevels(array $levels): array
    {
        return array_map(fn ($l) => ['title' => $l['title'], 'code' => $l['code'], 'lines' => substr_count(self::normalize($l['output']), "\n") + 1], $levels);
    }
}
