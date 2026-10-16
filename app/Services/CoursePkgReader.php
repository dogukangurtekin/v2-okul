<?php

namespace App\Services;

/** v1 .coursepkg: "COURSEPKG2" + BOUNDARY basligi + multipart (manifest.json + istege bagli kapak ikilisi) ya da duz JSON. */
class CoursePkgReader
{
    /** @return array{manifest: array, cover: ?string}|null */
    public function read(string $raw): ?array
    {
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;

        if (str_starts_with($raw, 'COURSEPKG2')) {
            return $this->readMultipart($raw);
        }
        $json = json_decode($raw, true);

        return is_array($json) ? ['manifest' => $json, 'cover' => null] : null;
    }

    private function readMultipart(string $raw): ?array
    {
        if (! preg_match('/^BOUNDARY:([A-Za-z0-9._\-]{8,100})\r?$/m', substr($raw, 0, 400), $m)) {
            return null;
        }
        $boundary = $m[1];
        $headerEnd = preg_match('/\r?\n\r?\n/', $raw, $hm, PREG_OFFSET_CAPTURE) ? $hm[0][1] + strlen($hm[0][0]) : null;
        if ($headerEnd === null) {
            return null;
        }
        $body = substr($raw, $headerEnd);

        $manifest = null;
        $cover = null;
        foreach (preg_split('/(?:^|\r?\n)--'.preg_quote($boundary, '/').'(?:--)?(?:\r?\n|$)/', $body) as $part) {
            if (trim($part) === '') {
                continue;
            }
            $split = preg_split('/\r?\n\r?\n/', $part, 2);
            if (count($split) !== 2) {
                continue;
            }
            [$headers, $content] = $split;
            if (stripos($headers, 'application/json') !== false) {
                $manifest = json_decode(trim($content), true);
            } elseif (preg_match('/Content-Type:\s*image\/(?:png|jpe?g|gif|webp)/i', $headers)) {
                $cover = $content;            // ayirici onundeki CRLF zaten ayiriciyla birlikte tuketildi; ikili veri oldugu gibi kalir
            }
        }

        return is_array($manifest) ? ['manifest' => $manifest, 'cover' => $cover] : null;
    }

    /**
     * Manifest'ten ders listesi: {course}, {courses:[...]}, duz liste ya da dogrudan tek ders (yedek JSON).
     *
     * @return list<array>
     */
    public function courses(array $manifest): array
    {
        if (isset($manifest['course']) && is_array($manifest['course'])) {
            // Bazi v1 paketlerinde (ornek: App Inventor betigi) slides/curriculum/category... "course" icinde degil, manifest kokunde.
            $rootKeys = ['slides', 'curriculum', 'lesson_description', 'category', 'difficulty', 'lesson_payload', 'cover_image', 'cover_image_data', 'sub_courses'];

            return [$manifest['course'] + array_intersect_key($manifest, array_flip($rootKeys))];
        }
        if (isset($manifest['courses']) && is_array($manifest['courses'])) {
            return array_values(array_filter($manifest['courses'], 'is_array'));
        }
        if (array_is_list($manifest)) {
            return array_values(array_filter($manifest, 'is_array'));
        }
        if (isset($manifest['name']) || isset($manifest['lesson_payload']) || isset($manifest['slides'])) {
            return [$manifest];
        }

        return [];
    }

    public static function isV2(array $manifest): bool
    {
        return ($manifest['format'] ?? null) === 'v2-okul.course';
    }
}
