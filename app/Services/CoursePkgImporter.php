<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseSlide;
use App\Models\User;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * v1 .coursepkg derslerini v2 ders modeline cevirir.
 *
 * v1 slayti tek parcada icerik + gorsel + video + kod + soru tasir; v2 slayti tek turdedir. Bu yuzden:
 *  - icerik/gorsel/video/kod -> tek slayt (text|image|video|code), HTML temizlenerek 'html' alaninda saklanir
 *  - soru varsa -> ayri bir 'quiz' slayti (choice | short | matching)
 * Cevrilemeyen her sey rapora uyari olarak yazilir; sessizce kaybolmaz.
 */
class CoursePkgImporter
{
    private array $report = ['courses' => [], 'warnings' => [], 'errors' => []];
    private ?CourseImageStore $images = null;
    private string $ctx = '';

    public function __construct(private CoursePkgReader $reader) {}

    /** @return array{courses: list<array>, warnings: list<string>, errors: list<string>} */
    public function import(string $raw, User $by, string $fileLabel = ''): array
    {
        $this->report = ['courses' => [], 'warnings' => [], 'errors' => []];
        $pkg = $this->reader->read($raw);
        if (! $pkg) {
            $this->report['errors'][] = ($fileLabel ? "$fileLabel: " : '').'Dosya okunamadı (geçerli bir .coursepkg değil).';

            return $this->report;
        }
        $courses = $this->reader->courses($pkg['manifest']);
        if (! $courses) {
            $this->report['errors'][] = ($fileLabel ? "$fileLabel: " : '').'Dosyada ders bulunamadı.';

            return $this->report;
        }

        foreach ($courses as $i => $c) {
            $this->images = new CourseImageStore('slides/'.date('Ym').'/'.Str::random(10));
            try {
                DB::transaction(fn () => $this->createCourse($c, null, $by, $i === 0 ? $pkg['cover'] : null));
            } catch (Throwable $e) {
                $this->images->rollback();
                $this->report['errors'][] = (($c['name'] ?? 'Ders').': ').'içe aktarılamadı — '.mb_substr($e->getMessage(), 0, 200);
            }
        }

        return $this->report;
    }

    private function warn(string $msg): void
    {
        $this->report['warnings'][] = ($this->ctx !== '' ? $this->ctx.': ' : '').$msg;
    }

    private function createCourse(array $c, ?Course $parent, User $by, ?string $coverBinary): Course
    {
        $payload = is_array($c['lesson_payload'] ?? null) ? $c['lesson_payload'] : [];
        $name = trim(HtmlSanitizer::toText((string) ($c['name'] ?? ''))) ?: 'İçe aktarılan ders';
        $this->ctx = $name;

        $slides = $this->firstNonEmptyList($c['slides'] ?? null, $payload['slides'] ?? null);
        $curriculum = is_array($c['curriculum'] ?? null) && $c['curriculum'] ? $c['curriculum'] : (is_array($payload['curriculum'] ?? null) ? $payload['curriculum'] : []);
        $desc = trim(HtmlSanitizer::toText((string) ($c['lesson_description'] ?? $payload['lesson_description'] ?? ''))) ?: (string) ($curriculum['konu'] ?? '');

        $course = Course::create([
            'parent_id' => $parent?->id, 'name' => mb_substr($name, 0, 255), 'code' => $this->uniqueCode((string) ($c['code'] ?? ''), $name),
            'description' => $desc ?: null,
            'category' => ($v = trim((string) ($c['category'] ?? $payload['category'] ?? ''))) !== '' ? mb_substr($v, 0, 80) : null,
            'difficulty' => ($v = trim((string) ($c['difficulty'] ?? $payload['difficulty'] ?? ''))) !== '' ? mb_substr($v, 0, 30) : null,
            'curriculum' => $this->cleanCurriculum($curriculum),
            'created_by' => $by->id, 'teacher_id' => $by->teacher?->id,
            'sort_order' => (int) ($c['sort_order'] ?? 0), 'is_active' => (bool) ($c['is_active'] ?? true),
        ]);

        // Kapak: paketteki ikili parca, yoksa cover_image_data (data URL). v1'in sunucu yolu (kapak-gorseli/..) tasinamaz.
        $cover = $coverBinary ? $this->images->storeCover($coverBinary) : null;
        if (! $cover && ! empty($c['cover_image_data'])) {
            $url = $this->images->fromDataUrl((string) $c['cover_image_data']);
            $cover = $url ? ltrim(parse_url($url, PHP_URL_PATH) ?? '', '/') : null;
        }
        if ($cover) {
            $course->update(['cover_path' => preg_replace('#^storage/#', '', $cover)]);
        } elseif (! empty($c['cover_image'] ?? $payload['cover_image'] ?? '')) {
            $this->warn('kapak görseli pakette yok (v1 sunucu yolu taşınamaz); kapağı elle yükleyin.');
        }

        if (! $slides) {
            $this->warn('pakette slayt bulunamadı; ders boş oluşturuldu (paket biçimi tanınmıyor olabilir).');
        }
        $pos = 0;
        $stats = ['slides' => 0, 'quizzes' => 0];
        $layoutLost = 0;
        foreach ($slides as $i => $s) {
            if (! is_array($s)) {
                continue;
            }
            foreach ($this->convertSlide($s, $i + 1) as $slide) {
                CourseSlide::create($slide + ['course_id' => $course->id, 'position' => $pos++]);
                $stats[$slide['type'] === 'quiz' ? 'quizzes' : 'slides']++;
                $layoutLost += ! empty($slide['content']['v1']['v1_layout_dropped']) ? 1 : 0;
            }
        }
        if ($layoutLost) {
            $this->warn("$layoutLost slaytın v1 yerleşimi/arka planı (bölünmüş ekran, gradyan vb.) v2'de yok; içerik ve resimler korundu, görünüm sade.");
        }
        $this->report['courses'][] = ['id' => $course->id, 'name' => $course->name, 'code' => $course->code, 'slides' => $stats['slides'], 'quizzes' => $stats['quizzes'], 'sub' => $parent !== null, 'v1_slides' => count($slides)];

        // Alt dersler (yalniz bir seviye: v1'de de bir seviyeydi)
        if (! $parent) {
            foreach ((array) ($c['sub_courses'] ?? []) as $sub) {
                if (is_array($sub)) {
                    $this->createCourse($sub, $course, $by, null);
                    $this->ctx = $name;
                }
            }
        }

        return $course;
    }

    private function firstNonEmptyList(...$lists): array
    {
        foreach ($lists as $l) {
            if (is_array($l) && $l) {
                return array_values($l);
            }
        }

        return [];
    }

    private function cleanCurriculum(array $cur): ?array
    {
        $list = fn ($v) => array_values(array_filter(array_map(fn ($x) => mb_substr(trim(HtmlSanitizer::toText((string) (is_array($x) ? ($x['text'] ?? '') : $x))), 0, 300), is_array($v) ? $v : []), fn ($x) => $x !== ''));
        $out = ['konu' => mb_substr(trim(HtmlSanitizer::toText((string) ($cur['konu'] ?? ''))), 0, 300), 'kazanimlar' => $list($cur['kazanimlar'] ?? []), 'etkinlikler' => $list($cur['etkinlikler'] ?? [])];

        return ($out['konu'] || $out['kazanimlar'] || $out['etkinlikler']) ? $out : null;
    }

    private function uniqueCode(string $code, string $name): string
    {
        $base = mb_substr(Str::upper(Str::slug($code ?: $name, '-')) ?: 'DERS', 0, 24);
        $try = $base;
        for ($i = 2; Course::where('code', $try)->exists(); $i++) {
            $try = $base.'-'.$i;
        }

        return $try;
    }

    // ------------------------------------------------------------------ slayt

    /** @return list<array{type:string,title:?string,content:array}> */
    public function convertSlide(array $s, int $no): array
    {
        $title = mb_substr(trim(HtmlSanitizer::toText((string) ($s['title'] ?? ''))), 0, 255) ?: null;
        $label = '#'.$no.($title ? " “{$title}”" : '');
        $lm = is_array($s['layout_meta'] ?? null) ? $s['layout_meta'] : [];
        $imgResolver = function (string $src) use ($label) {
            if (str_starts_with($src, 'data:')) {
                $u = $this->images->fromDataUrl($src);
                if (! $u) {
                    $this->warn("slayt $label: gömülü resim geçersiz/çok büyük, atlandı.");
                }

                return $u;
            }
            if (preg_match('#^https?://#i', $src)) {
                return $src;
            }
            $this->warn("slayt $label: v1 sunucusundaki resim yolu ($src) taşınamaz, atlandı.");

            return null;
        };
        $san = new HtmlSanitizer($imgResolver);

        // --- icerik: content, yoksa layout_meta icindeki metin kolonlari
        $rawHtml = (string) ($s['content'] ?? '');
        if (trim(HtmlSanitizer::toText($rawHtml)) === '' && ! preg_match('/<img/i', $rawHtml)) {
            $parts = [];
            foreach (['text', 'left', 'right'] as $k) {
                $col = $lm[$k] ?? null;
                if (is_array($col) && ($col['type'] ?? 'text') === 'text') {
                    $parts[] = (string) (($col['html'] ?? '') ?: ($col['text'] ?? ''));
                }
            }
            $rawHtml = implode("\n", array_filter($parts));
        }
        $html = $san->clean($rawHtml);

        // --- gorsel: slayt alani, yoksa layout_meta
        $imageUrl = null;
        foreach ([$s['image_url'] ?? '', data_get($lm, 'media.image_url'), data_get($lm, 'media.url'), data_get($lm, 'media.src'), data_get($lm, 'right.image_url'), data_get($lm, 'left.image_url'), data_get($lm, 'image_url')] as $cand) {
            if (is_string($cand) && trim($cand) !== '') {
                $imageUrl = $imgResolver(trim($cand));
                if ($imageUrl) {
                    break;
                }
            }
        }
        if ($imageUrl && str_contains($html, $imageUrl)) {
            $imageUrl = null;                                             // ayni resim icerikte zaten var
        }

        // --- video / dosya / kod / yonerge
        $video = trim((string) ($s['video_url'] ?? ''));
        if ($video !== '' && ! preg_match('#^https?://#i', $video)) {
            $this->warn("slayt $label: video bağlantısı http(s) değil, atlandı.");
            $video = '';
        }
        $file = trim((string) ($s['file_url'] ?? ''));
        if ($file !== '') {
            if (preg_match('#^https?://#i', $file)) {
                $html .= '<p><a href="'.e($file).'">Ek dosya</a></p>';
                $html = $san->clean($html);
            } else {
                $this->warn("slayt $label: ek dosya (v1 sunucusunda) taşınamaz, atlandı.");
            }
        }
        $code = trim((string) ($s['code'] ?? ''));
        $note = trim(HtmlSanitizer::toText((string) ($s['instructions'] ?? '')));

        $meta = array_filter(['layout' => $s['layout'] ?? null, 'xp' => $s['xp'] ?? null, 'points' => $s['points'] ?? null, 'time_limit' => $s['time_limit'] ?? null, 'double_points' => $s['double_points'] ?? null], fn ($v) => $v !== null && $v !== '' && $v !== 0 && $v !== false);
        if (in_array($s['layout'] ?? '', ['split', 'image', 'summary'], true) || data_get($s, 'slide_background.value')) {
            $meta['v1_layout_dropped'] = true;                           // yerlesim/arka plan v2'de yok
        }

        $out = [];
        $text = HtmlSanitizer::toText($html);
        $it = (string) ($s['interaction_type'] ?? 'none');
        $hasQuestion = $it !== '' && $it !== 'none';

        // Soru slaytlarinda v1 icerigi genelde "<h2>soru</h2>" tekrari: anlamli ek metin yoksa icerik slaydi acma.
        $prompt = trim(HtmlSanitizer::toText((string) ($s['question_prompt'] ?? '')));
        $norm = fn ($x) => mb_strtolower(preg_replace('/\s+/u', ' ', trim($x)));
        $redundant = $hasQuestion && ($text === '' || $norm($text) === $norm($prompt) || $norm($text) === $norm((string) $title));
        $hasMedia = $imageUrl || $video || $code;

        if (! $redundant || $hasMedia) {
            if ($code !== '') {
                if ($html === '' && ! $imageUrl && ! $video) {
                    $type = 'code';
                } else {
                    $html .= '<pre><code>'.e($code).'</code></pre>';
                    $text = trim($text."\n\n".$code);
                }
            }
            $type ??= $video ? 'video' : ($imageUrl ? 'image' : 'text');
            $content = ['body' => $type === 'code' ? $code : $text];
            if ($type !== 'code' && $html !== '') {
                $content['html'] = $html;
            }
            if ($type === 'video') {
                $content['url'] = $video;
                if ($imageUrl) {
                    $content['html'] = ($content['html'] ?? '').'<p><img src="'.e($imageUrl).'" alt=""></p>';
                }
            } elseif ($type === 'image') {
                $content['url'] = $imageUrl;
            }
            if ($note !== '') {
                $content['note'] = $note;
            }
            if ($type !== 'code' && $text === '' && ! isset($content['url'])) {
                $this->warn("slayt $label: içerik boş, atlandı.");
            } else {
                $out[] = ['type' => $type, 'title' => $title, 'content' => $content + ['v1' => $meta]];
            }
        }

        if ($hasQuestion) {
            $q = $this->convertQuestion($s, $it, $prompt ?: ($title ?? ''), $label);
            if ($q) {
                if ($note !== '' && ! $out) {
                    $q['note'] = $note;
                }
                $out[] = ['type' => 'quiz', 'title' => $title, 'content' => $q + ['v1' => $meta]];
            }
        }

        return $out;
    }

    /** @return array<string,mixed>|null v2 quiz icerigi */
    private function convertQuestion(array $s, string $it, string $prompt, string $label): ?array
    {
        $q = is_array($s['question'] ?? null) ? $s['question'] : [];
        $plain = fn ($x) => mb_substr(trim(HtmlSanitizer::toText((string) (is_array($x) ? ($x['text'] ?? '') : $x))), 0, 500);
        $truthy = fn ($x) => in_array($x, [true, 1, '1', 'true', 'yes', 'on'], true);

        switch ($it) {
            case 'multiple_choice':
                $options = [];
                $correct = [];
                foreach ((array) ($q['options'] ?? []) as $o) {
                    if (($t = $plain($o)) !== '') {
                        if (is_array($o) && $truthy($o['correct'] ?? false)) {
                            $correct[] = count($options);
                        }
                        $options[] = $t;
                    }
                }
                if (count($options) < 2) {
                    $this->warn("slayt $label: çoktan seçmeli soruda 2'den az şık, soru atlandı.");

                    return null;
                }
                if (! $correct) {
                    $this->warn("slayt $label: doğru şık işaretli değil — ilk şık doğru kabul edildi, KONTROL EDİN.");
                } elseif (count($correct) > 1) {
                    $this->warn("slayt $label: birden çok doğru şık var; v2 tek doğru cevap destekler, ilki alındı.");
                }

                return ['mode' => 'choice', 'question' => $prompt, 'options' => $options, 'answer' => $correct[0] ?? 0];

            case 'true_false':
                $opts = array_values(array_filter((array) ($q['options'] ?? []), 'is_array'));
                if (count($opts) >= 2) {
                    $idx = 0;
                    foreach ($opts as $k => $o) {
                        if ($truthy($o['correct'] ?? false)) {
                            $idx = preg_match('/^\s*yan/iu', (string) ($o['text'] ?? '')) ? 1 : 0;
                        }
                    }
                } elseif (count($opts) === 1) {
                    $idx = $truthy($opts[0]['correct'] ?? false) ? 0 : 1;      // tek secenek: onermenin dogrulugu
                } else {
                    $this->warn("slayt $label: doğru/yanlış sorusunda cevap bilgisi yok, soru atlandı.");

                    return null;
                }

                return ['mode' => 'choice', 'question' => $prompt, 'options' => ['Doğru', 'Yanlış'], 'answer' => $idx];

            case 'short_answer':
                $ans = trim((string) ($q['answer'] ?? ''));
                if ($ans === '') {
                    $this->warn("slayt $label: kısa cevap sorusunda cevap yok, soru atlandı.");

                    return null;
                }

                return ['mode' => 'short', 'question' => $prompt, 'answers' => array_values(array_filter(array_map('trim', preg_split('/\s*\|\s*/', $ans))))];

            case 'matching':
            case 'drag_drop':
                $pairs = [];
                foreach ((array) ($q['pairs'] ?? $q['items'] ?? []) as $p) {
                    if (is_array($p)) {
                        $l = $plain($p['left'] ?? $p['text'] ?? '');
                        $r = $plain($p['right'] ?? $p['target'] ?? $p['answer'] ?? '');
                        if ($l !== '' && $r !== '') {
                            $pairs[] = [$l, $r];
                        }
                    }
                }
                if (count($pairs) < 2) {
                    $this->warn("slayt $label: eşleştirme sorusunda 2'den az geçerli çift, soru atlandı.");

                    return null;
                }

                return ['mode' => 'matching', 'question' => $prompt, 'pairs' => $pairs];
        }

        $this->warn("slayt $label: desteklenmeyen soru türü “{$it}”, etkileşim atlandı.");

        return null;
    }
}
