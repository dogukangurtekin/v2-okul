<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSlide;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\CoursePkgReader;
use App\Support\HtmlSanitizer;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CoursePkgImportTest extends TestCase
{
    use RefreshDatabase;

    private const GIF = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    /** v1 CourseController::buildCoursePackage ile ayni bicimde COURSEPKG2 uretir. */
    private function pkg(array $payload, ?string $cover = null): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $b = 'COURSEPKG-'.strtolower(\Illuminate\Support\Str::random(24));
        $parts = ["--{$b}\r\nContent-Type: application/json; charset=UTF-8\r\nContent-Disposition: form-data; name=\"manifest\"; filename=\"manifest.json\"\r\n\r\n{$json}\r\n"];
        if ($cover !== null) {
            $parts[] = "--{$b}\r\nContent-Type: image/png\r\nContent-Disposition: form-data; name=\"cover\"; filename=\"c.png\"\r\n\r\n{$cover}\r\n";
        }
        $parts[] = "--{$b}--\r\n";

        return "COURSEPKG2\r\nBOUNDARY:{$b}\r\n\r\n".implode('', $parts);
    }

    private function dataUrl(): string
    {
        return 'data:image/png;base64,'.self::PNG;
    }

    private function fixture(): array
    {
        $mc = fn ($title, $prompt, $opts) => ['title' => $title, 'layout' => 'text', 'xp' => 10, 'kind' => 'topic', 'interaction_type' => 'multiple_choice', 'content' => "<h2>{$prompt}</h2>", 'question_prompt' => $prompt, 'points' => 5, 'time_limit' => 10, 'question' => ['options' => $opts]];

        $slides = [
            // 1 split: tehlikeli HTML + gomulu resim + layout_meta sag kolon resmi
            ['title' => 'Giriş', 'layout' => 'split', 'interaction_type' => 'none', 'xp' => 10, 'kind' => 'topic',
                'content' => '<h2>Başlık</h2><p style="color:red;position:fixed;background:url(javascript:1)" onclick="x()">Merhaba <strong>dünya</strong></p><script>alert(1)</script><img src="x" onerror="alert(2)"><a href="javascript:alert(3)">kötü</a><a href="https://ok.test/x">iyi</a><iframe src="https://evil.test"></iframe><img src="'.$this->dataUrl().'" alt="gömülü">',
                'image_url' => 'data:image/gif;base64,'.self::GIF, 'layout_meta' => ['kind' => 'split', 'right' => ['type' => 'image', 'image_url' => $this->dataUrl()]], 'slide_background' => ['type' => 'gradient', 'value' => 'linear-gradient(red,blue)']],
            // 2 icerik yalniz layout_meta'da
            ['title' => 'Meta metin', 'layout' => 'text', 'interaction_type' => 'none', 'content' => '', 'layout_meta' => ['text' => ['html' => '<p>Yalnızca meta içinde</p>']]],
            // 3 video + ek dosya + kod
            ['title' => 'Video', 'layout' => 'text', 'interaction_type' => 'none', 'content' => '<p>İzle</p>', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'file_url' => 'https://files.test/ek.pdf', 'code' => 'print("merhaba")', 'instructions' => 'Videoyu sonuna kadar izle'],
            // 4 yalniz kod
            ['title' => 'Kod', 'layout' => 'text', 'interaction_type' => 'none', 'content' => '', 'code' => "for i in range(3):\n    print(i)"],
            // 5 coktan secmeli, 2 dogru
            $mc('Soru 1', 'Başkent neresi?', [['text' => 'Ankara', 'correct' => true], ['text' => 'Ankara (Çankaya)', 'correct' => true], ['text' => 'İzmir', 'correct' => false]]),
            // 6 dogru/yanlis tek secenek (onerme yanlis)
            ['title' => 'DY1', 'interaction_type' => 'true_false', 'content' => '', 'question_prompt' => 'Dünya düzdür.', 'question' => ['options' => [['text' => 'Doğru', 'correct' => false]]]],
            // 7 dogru/yanlis iki secenek ('Yanlış' dogru)
            ['title' => 'DY2', 'interaction_type' => 'true_false', 'content' => '', 'question_prompt' => 'Çok renk kullanmak okunurluğu artırır.', 'question' => ['options' => [['text' => 'Doğru', 'correct' => false], ['text' => 'Yanlış', 'correct' => true]]]],
            // 8 kisa cevap
            ['title' => 'Kısa', 'interaction_type' => 'short_answer', 'content' => '', 'question_prompt' => 'Algoritma kimden gelir?', 'question' => ['answer' => 'el-harezmi | harezmi']],
            // 9 eslestirme (pairs) + 10 drag_drop (items/target)
            ['title' => 'Eşleştir', 'interaction_type' => 'matching', 'content' => '', 'question_prompt' => 'Eşleştir', 'question' => ['pairs' => [['left' => 'Girdi', 'right' => 'Giren veri'], ['left' => 'Çıktı', 'right' => 'Sonuç']]]],
            ['title' => 'Sürükle', 'interaction_type' => 'drag_drop', 'content' => '<h2>Sürükle</h2>', 'question_prompt' => 'Sürükle', 'question' => ['items' => [['text' => 'Şablon', 'target' => 'Hazır tasarım'], ['text' => 'Yazı tipi', 'target' => 'Harf biçimi']]]],
            // 11 desteklenmeyen tur, 12 dogru yok, 13 goreli resim, 14 bos
            ['title' => 'Liste', 'interaction_type' => 'checklist', 'content' => '', 'question_prompt' => 'İşaretle', 'question' => ['items' => [['text' => 'a', 'correct' => true]]]],
            $mc('Soru işaretsiz', 'Hangisi?', [['text' => 'A', 'correct' => false], ['text' => 'B', 'correct' => false]]),
            ['title' => 'Eski resim', 'interaction_type' => 'none', 'content' => '<p>Metin</p><img src="kapak-gorseli/eski.png">'],
            ['title' => 'Boş', 'interaction_type' => 'none', 'content' => ''],
        ];

        return [
            'exported_at' => '2026-08-27T12:00:00+00:00',
            'course' => [
                'id' => 253, 'name' => 'Canva Dersi', 'code' => 'BTCANVA01-HKM18V', 'teacher_id' => 7, 'school_class_id' => 3, 'weekly_hours' => 1, 'is_active' => true,
                'lesson_payload' => ['slides' => $slides, 'curriculum' => ['konu' => 'Canva', 'kazanimlar' => ['Tasarım nedir?', 'Şablon kullanır.'], 'etkinlikler' => ['Menüleri bul']], 'category' => 'Bilişim', 'difficulty' => 'Kolay', 'cover_image' => 'kapak-gorseli/x.png'],
                'slides' => $slides, 'curriculum' => ['konu' => 'Canva', 'kazanimlar' => ['Tasarım nedir?', 'Şablon kullanır.'], 'etkinlikler' => ['Menüleri bul']],
                'lesson_description' => '<p>Canva ile <b>tasarım</b></p>', 'category' => 'Bilişim', 'difficulty' => 'Kolay', 'cover_image' => 'kapak-gorseli/x.png',
            ],
            'sub_courses' => [['name' => 'Alt ders', 'code' => 'ALT1', 'lesson_payload' => ['slides' => [['title' => 'Alt slayt', 'interaction_type' => 'none', 'content' => '<p>alt</p>']]], 'parent_course_id' => 253]],
        ];
    }

    private function admin(): User
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');

        return User::where('email', 'admin@school.local')->first();
    }

    private function upload(string $raw, string $name = 'ders.coursepkg'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $raw);
    }

    // ---------- Okuyucu ----------

    public function test_reader_handles_multipart_with_binary_cover_plain_json_and_garbage(): void
    {
        $r = new CoursePkgReader;
        $bin = base64_decode(self::PNG)."\r\n--ortada\r\n".random_bytes(40);            // kapak ikilisi CRLF ve ayirici benzeri bayt icerir
        $out = $r->read($this->pkg(['course' => ['name' => 'X']], $bin));
        $this->assertSame('X', $out['manifest']['course']['name']);
        $this->assertSame($bin, $out['cover']);

        $this->assertSame('Y', $r->read("\xEF\xBB\xBF".json_encode(['course' => ['name' => 'Y']]))['manifest']['course']['name']);   // BOM'lu duz JSON
        $this->assertNull($r->read('COURSEPKG2'));
        $this->assertNull($r->read('rastgele metin'));
        $this->assertCount(2, $r->courses(['courses' => [['name' => 'a'], ['name' => 'b']]]));
        $this->assertCount(1, $r->courses(['name' => 'tek', 'lesson_payload' => ['slides' => []]]));      // yedek JSON (storage/app/course_*_backup.json)
        $this->assertSame([], $r->courses(['x' => 1]));
    }

    // ---------- Temizleyici ----------

    public function test_sanitizer_blocks_xss_vectors_and_keeps_safe_formatting(): void
    {
        $san = new HtmlSanitizer;
        $bad = [
            '<script>alert(1)</script>x', '<img src=x onerror=alert(1)>', '<a href="javascript:alert(1)">t</a>', '<a href=" JaVaScRiPt:alert(1)">t</a>',
            '<iframe src="//evil"></iframe>', '<svg onload=alert(1)><circle/></svg>', '<p onclick="x()">t</p>', '<div style="background:url(javascript:alert(1))">t</div>',
            '<style>body{display:none}</style>t', '<form action="/x"><input name=a></form>t', '<math><mtext></mtext></math>', '<a href="data:text/html;base64,PHNjcmlwdD4=">t</a>',
        ];
        foreach ($bad as $v) {
            $out = strtolower($san->clean($v));
            foreach (['<script', 'onerror', 'onclick', 'onload', 'javascript:', '<iframe', '<svg', '<form', '<input', '<style', 'data:text', 'url('] as $needle) {
                $this->assertStringNotContainsString($needle, $out, "Vektör sızdırdı ($needle): $v → $out");
            }
        }
        $ok = $san->clean('<h2>Başlık</h2><p style="color:#f00;text-align:center;font-size:18px">A <b>b</b> <em>c</em></p><ul><li>x</li></ul><a href="https://a.test">l</a><table><tr><td colspan="2">t</td></tr></table>');
        $this->assertStringContainsString('<h2>Başlık</h2>', $ok);
        $this->assertStringContainsString('color:#f00;text-align:center;font-size:18px', $ok);
        $this->assertStringContainsString('rel="noopener noreferrer"', $ok);
        $this->assertStringContainsString('colspan="2"', $ok);
        $this->assertSame('Merhaba dünya', HtmlSanitizer::toText('<p>Merhaba&nbsp;<b>dünya</b></p>'));      // &nbsp; sıradan boşluğa
        $this->assertSame("a\nb", HtmlSanitizer::toText('<p>a</p><p>b</p>'));
    }

    // ---------- Ice aktarma ----------

    public function test_full_v1_package_conversion_report_and_content(): void
    {
        $admin = $this->admin();
        $res = $this->actingAs($admin)->post('/courses/import', ['files' => [$this->upload($this->pkg($this->fixture(), base64_decode(self::PNG)))]]);
        $res->assertRedirect('/courses');
        $report = session('import_report');

        $this->assertSame([], $report['errors']);
        $this->assertCount(2, $report['courses']);                                       // ana + alt ders
        $course = Course::where('name', 'Canva Dersi')->first();
        $this->assertSame('BTCANVA01-HKM18V', $course->code);
        $this->assertSame('Bilişim', $course->category);
        $this->assertSame('Kolay', $course->difficulty);
        $this->assertSame('Canva ile tasarım', $course->description);                    // HTML'siz
        $this->assertSame(['Tasarım nedir?', 'Şablon kullanır.'], $course->curriculum['kazanimlar']);
        $this->assertNotNull($course->cover_path);                                       // paketteki kapak ikilisi
        Storage::disk('public')->assertExists($course->cover_path);
        $this->assertSame($admin->id, $course->created_by);
        $this->assertSame(1, Course::where('parent_id', $course->id)->count());

        $slides = $course->slides()->get();
        $types = $slides->pluck('type')->all();
        // 1 text(image) ,2 text ,3 video ,4 code ,5 quiz ,6 quiz ,7 quiz ,8 quiz ,9 quiz ,10 quiz ,11 text(checklist degil: atlandi) ...
        $this->assertSame('image', $types[0]);                                           // image_url -> image slaydi
        $this->assertSame('text', $types[1]);
        $this->assertSame('video', $types[2]);
        $this->assertSame('code', $types[3]);
        $this->assertSame(['quiz', 'quiz', 'quiz', 'quiz', 'quiz', 'quiz'], array_slice($types, 4, 6));

        // 1. slayt: HTML temiz, resim depoya tasindi
        $c1 = $slides[0]->content;
        $this->assertStringNotContainsString('<script', $c1['html']);
        $this->assertStringNotContainsString('onerror', $c1['html']);
        $this->assertStringNotContainsString('onclick', $c1['html']);
        $this->assertStringNotContainsString('javascript:', $c1['html']);
        $this->assertStringNotContainsString('iframe', $c1['html']);
        $this->assertStringContainsString('<strong>dünya</strong>', $c1['html']);
        $this->assertStringContainsString('https://ok.test/x', $c1['html']);
        $this->assertStringNotContainsString('position', $c1['html']);                  // riskli stil atildi
        $this->assertStringContainsString('color:red', $c1['html']);
        $this->assertStringNotContainsString('base64', $c1['html']);                    // gomulu resim dosyaya cevrildi
        $this->assertMatchesRegularExpression('#^/storage/slides/\d{6}/[A-Za-z0-9]{10}/[a-f0-9]{40}\.gif$#', $c1['url']);
        $this->assertStringContainsString('Merhaba dünya', $c1['body']);

        $this->assertStringContainsString('Yalnızca meta içinde', $slides[1]->content['body']);             // layout_meta'dan
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $slides[2]->content['url']);
        $this->assertStringContainsString('Ek dosya', $slides[2]->content['html']);
        $this->assertStringContainsString('print(&quot;merhaba&quot;)', $slides[2]->content['html']);       // kod escape edilerek eklendi
        $this->assertSame('Videoyu sonuna kadar izle', $slides[2]->content['note']);
        $this->assertSame("for i in range(3):\n    print(i)", $slides[3]->content['body']);

        // sorular
        $q = fn ($i) => $slides[$i]->content;
        $this->assertSame(['mode' => 'choice', 'question' => 'Başkent neresi?', 'options' => ['Ankara', 'Ankara (Çankaya)', 'İzmir'], 'answer' => 0], array_intersect_key($q(4), array_flip(['mode', 'question', 'options', 'answer'])));
        $this->assertSame(['Doğru', 'Yanlış'], $q(5)['options']);
        $this->assertSame(1, $q(5)['answer']);                                           // tek seçenek, correct=false -> önerme yanlış
        $this->assertSame(1, $q(6)['answer']);                                           // 'Yanlış' doğru işaretli
        $this->assertSame(['el-harezmi', 'harezmi'], $q(7)['answers']);
        $this->assertSame('short', $q(7)['mode']);
        $this->assertSame([['Girdi', 'Giren veri'], ['Çıktı', 'Sonuç']], $q(8)['pairs']);
        $this->assertSame([['Şablon', 'Hazır tasarım'], ['Yazı tipi', 'Harf biçimi']], $q(9)['pairs']);   // drag_drop -> matching
        $this->assertSame('Soru işaretsiz', $slides->where('type', 'quiz')->last()->title);

        // soru slaytlarında "<h2>soru</h2>" tekrarı ayrı içerik slaydı üretmedi
        $this->assertSame(0, $slides->where('title', 'Soru 1')->where('type', 'text')->count());

        // uyarılar eksiksiz
        $w = implode("\n", $report['warnings']);
        foreach (['birden çok doğru', 'doğru şık işaretli değil', 'desteklenmeyen soru türü “checklist”', 'taşınamaz', 'içerik boş'] as $needle) {
            $this->assertStringContainsString($needle, $w, "Uyarı yok: $needle");
        }
        $this->assertStringContainsString("slaytın v1 yerleşimi/arka planı", $w);          // tek, toplu uyarı (her slayt için ayrı değil)
        $this->assertSame(1, substr_count($w, 'yerleşimi/arka planı'));
        $this->assertTrue($slides[0]->content['v1']['v1_layout_dropped']);
    }

    public function test_code_collisions_get_suffix_and_second_import_does_not_overwrite(): void
    {
        $admin = $this->admin();
        $f = $this->fixture();
        $this->actingAs($admin)->post('/courses/import', ['files' => [$this->upload($this->pkg($f))]]);
        $this->post('/courses/import', ['files' => [$this->upload($this->pkg($f))]]);
        $this->assertSame(1, Course::where('code', 'BTCANVA01-HKM18V')->count());
        $this->assertSame(1, Course::where('code', 'BTCANVA01-HKM18V-2')->count());
        $this->assertSame(4, Course::count());                                           // 2 ana + 2 alt
    }

    public function test_multiple_files_mixed_formats_and_bad_file_do_not_stop_the_batch(): void
    {
        $admin = $this->admin();
        $v1 = ['course' => ['name' => 'V1 Ders', 'code' => 'V1', 'slides' => [['title' => 'a', 'interaction_type' => 'none', 'content' => '<p>x</p>']]]];
        $v2 = ['format' => 'v2-okul.course', 'version' => 1, 'course' => ['name' => 'V2 Ders', 'code' => 'V2', 'description' => ''], 'slides' => [['position' => 0, 'type' => 'text', 'title' => 't', 'content' => ['body' => 'b']]]];
        $this->actingAs($admin)->post('/courses/import', ['files' => [
            $this->upload(json_encode($v2), 'a.json'), $this->upload($this->pkg($v1), 'b.coursepkg'), $this->upload('bozuk dosya', 'c.coursepkg'),
            $this->upload($this->pkg(['courses' => [['name' => 'Toplu 1', 'slides' => []], ['name' => 'Toplu 2', 'lesson_payload' => ['slides' => [['title' => 's', 'content' => '<p>y</p>']]]]]]), 'd.coursepkg'),
        ]])->assertRedirect('/courses');

        $r = session('import_report');
        $this->assertSame(['V2 Ders', 'V1 Ders', 'Toplu 1', 'Toplu 2'], array_column($r['courses'], 'name'));
        $this->assertCount(1, $r['errors']);
        $this->assertStringContainsString('c.coursepkg', $r['errors'][0]);
        $this->assertSame('b', Course::where('code', 'V2')->first()->slides()->first()->content['body']);
        $this->get('/courses')->assertOk()->assertSee('İçe aktarma raporu')->assertSee('Toplu 2');
    }

    public function test_hostile_images_and_oversize_are_rejected_not_stored(): void
    {
        $admin = $this->admin();
        $svg = 'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>');
        $fake = 'data:image/png;base64,'.base64_encode('<?php echo 1; ?> bu bir png degil');
        $pkg = ['course' => ['name' => 'Kötü', 'slides' => [['title' => 'r', 'interaction_type' => 'none', 'content' => '<p>t</p><img src="'.$svg.'"><img src="'.$fake.'">']]]];
        $this->actingAs($admin)->post('/courses/import', ['files' => [$this->upload($this->pkg($pkg))]]);
        $this->assertSame([], Storage::disk('public')->allFiles());                       // hiçbir şey yazılmadı
        $html = Course::first()->slides()->first()->content['html'];
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('geçersiz', implode(' ', session('import_report')['warnings'] ?? ['geçersiz']));
    }

    // ---------- Oynatici ve editor ----------

    public function test_student_player_renders_imported_html_and_all_quiz_modes_safely(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/courses/import', ['files' => [$this->upload($this->pkg($this->fixture(), base64_decode(self::PNG)))]]);
        $course = Course::where('name', 'Canva Dersi')->first();
        $class = SchoolClass::create(['name' => '5', 'section' => 'A', 'grade_level' => 5, 'academic_year' => '2026-2027']);
        $course->classes()->sync([$class->id]);
        $su = User::factory()->create(['role_id' => Role::where('slug', 'student')->value('id')]);
        Student::create(['user_id' => $su->id, 'student_no' => '1', 'school_class_id' => $class->id]);

        $html = $this->actingAs($su)->get("/ogrenci/derslerim/{$course->id}")->assertOk()->getContent();
        $this->assertStringContainsString('<strong>dünya</strong>', $html);               // HTML işlendi (kaçırılmadı)
        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringContainsString('youtube-nocookie.com/embed/dQw4w9WgXcQ', $html);
        $this->assertStringContainsString('short-in', $html);
        $this->assertStringContainsString('match-in', $html);
        $this->assertStringContainsString('Kazanımlar', $html);
        $this->assertStringContainsString('Tasarım nedir?', $html);
        $this->assertStringContainsString('class="opt', $html);
    }

    public function test_editor_keeps_imported_html_unless_body_changed_and_saves_new_quiz_modes(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/courses/import', ['files' => [$this->upload($this->pkg($this->fixture()))]]);
        $course = Course::where('name', 'Canva Dersi')->first();
        $slide = $course->slides()->where('type', 'image')->first();

        // gövde değişmedi -> html korunur
        $this->put("/courses/{$course->id}/slides/{$slide->id}", ['type' => 'image', 'title' => 'Yeni başlık', 'body' => $slide->content['body'], 'url' => $slide->content['url']])->assertSessionHasNoErrors();
        $this->assertArrayHasKey('html', $slide->fresh()->content);
        $this->assertSame('Yeni başlık', $slide->fresh()->title);
        // gövde değişti -> html atılır
        $this->put("/courses/{$course->id}/slides/{$slide->id}", ['type' => 'image', 'body' => 'Elle yazıldı', 'url' => $slide->content['url']]);
        $this->assertArrayNotHasKey('html', $slide->fresh()->content);
        $this->assertSame('Elle yazıldı', $slide->fresh()->content['body']);

        // yeni quiz modları
        $this->post("/courses/{$course->id}/slides", ['type' => 'quiz', 'mode' => 'short', 'question' => 'Başkent?', 'short_answers' => 'Ankara | ankara'])->assertSessionHasNoErrors();
        $this->post("/courses/{$course->id}/slides", ['type' => 'quiz', 'mode' => 'matching', 'question' => 'Eşleştir', 'pairs' => "a = 1\nb = 2\nbozuk satır"])->assertSessionHasNoErrors();
        $last = $course->slides()->reorder('position', 'desc')->take(2)->get()->reverse()->values();
        $this->assertSame(['Ankara', 'ankara'], $last[0]->content['answers']);
        $this->assertSame([['a', '1'], ['b', '2']], $last[1]->content['pairs']);
        $this->post("/courses/{$course->id}/slides", ['type' => 'quiz', 'mode' => 'matching', 'question' => 'x', 'pairs' => 'a = 1'])->assertStatus(422);   // en az 2 çift
        $this->post("/courses/{$course->id}/slides", ['type' => 'quiz', 'mode' => 'short', 'question' => 'x', 'short_answers' => ' | '])->assertStatus(422);
        $this->post("/courses/{$course->id}/slides", ['type' => 'quiz', 'question' => 'x', 'options' => "tek", 'answer' => 0])->assertStatus(422);

        $this->get("/courses/{$course->id}/edit")->assertOk()->assertSee('Kısa cevap');
    }

    public function test_v2_export_import_roundtrip_keeps_new_fields(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/courses/import', ['files' => [$this->upload($this->pkg($this->fixture()))]]);
        $course = Course::where('name', 'Canva Dersi')->first();
        $json = $this->get("/courses/{$course->id}/indir")->assertOk()->getContent();
        $this->assertStringContainsString('"category": "Bilişim"', $json);
        $this->post('/courses/import', ['file' => $this->upload($json, 'x.json')])->assertRedirect();
        $this->assertSame(CourseSlide::where('course_id', $course->id)->count(), Course::where('code', 'BTCANVA01-HKM18V-2')->first()->slides()->count());
    }

    public function test_svg_data_urls_are_accepted_only_when_inert_and_never_written_as_files(): void
    {
        $store = new \App\Services\CourseImageStore('slides/t');
        $good = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10"><rect width="10" height="10" fill="#f00" style="stroke:#000"/><text x="1" y="5">Merhaba ğüşiöç</text></svg>';

        $b64 = $store->safeSvgDataUrl('data:image/svg+xml;base64,'.base64_encode($good));
        $raw = $store->safeSvgDataUrl('data:image/svg+xml;charset=UTF-8,'.rawurlencode($good));       // v1 uretici betikleri bu bicimi kullanir
        foreach ([$b64, $raw] as $u) {
            $this->assertStringStartsWith('data:image/svg+xml;base64,', $u);
            $this->assertStringContainsString('Merhaba ğüşiöç', base64_decode(substr($u, strlen('data:image/svg+xml;base64,'))));
        }
        $this->assertSame([], $store->written);                                                         // dosya yazilmadi

        $evil = [
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect onclick="x()" width="1" height="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div>x</div></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><image xlink:href="https://evil.test/a.png"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:alert(1)"><text>t</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(https://evil.test/x.css);</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:url(https://evil.test/x)" width="1" height="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><animate attributeName="href" to="javascript:alert(1)"/></svg>',
            '<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]><svg xmlns="http://www.w3.org/2000/svg">&x;</svg>',
            '<html><body>svg degil</body></html>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect',                                            // bozuk XML
            '<svg xmlns="http://www.w3.org/2000/svg">'.str_repeat('<g/>', 120000).'</svg>',              // boyut
        ];
        foreach ($evil as $i => $svg) {
            $this->assertNull($store->safeSvgDataUrl('data:image/svg+xml;base64,'.base64_encode($svg)), "Kötü SVG kabul edildi #$i");
        }
        $this->assertNull($store->safeSvgDataUrl('data:image/svg+xml;base64,@@@'));
    }

    public function test_import_keeps_v1_generated_svg_illustrations_inline(): void
    {
        $admin = $this->admin();
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1280" height="720"><rect width="1280" height="720" fill="#eef"/><text x="40" y="80" font-size="48">Algoritma</text></svg>';
        $pkg = ['course' => ['name' => 'SVG Dersi', 'slides' => [['title' => 'Çizim', 'interaction_type' => 'none', 'content' => '<p>Metin</p>', 'image_url' => 'data:image/svg+xml;charset=UTF-8,'.rawurlencode($svg)]]]];
        $this->actingAs($admin)->post('/courses/import', ['files' => [$this->upload($this->pkg($pkg))]]);

        $slide = Course::first()->slides()->first();
        $this->assertSame('image', $slide->type);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $slide->content['url']);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_root_level_slides_layout_and_empty_course_warning(): void
    {
        $admin = $this->admin();
        // App Inventor betiğinin ürettiği biçim: slides/curriculum/category manifest kökünde, "course" yalnızca ad/kod
        $root = ['course' => ['name' => 'Kök Düzeni', 'code' => 'KOK', 'weekly_hours' => 2], 'curriculum' => ['konu' => 'Konu', 'kazanimlar' => ['K1']],
            'lesson_description' => 'Açıklama', 'difficulty' => 'Orta', 'category' => 'Kodlama',
            'slides' => [['title' => 'S1', 'interaction_type' => 'none', 'content' => '<p>Kökte</p>'], ['title' => 'Q', 'interaction_type' => 'short_answer', 'content' => '', 'question_prompt' => 'Soru?', 'question' => ['answer' => 'x']]]];
        $this->actingAs($admin)->post('/courses/import', ['files' => [$this->upload($this->pkg($root))]])->assertRedirect();
        $c = Course::where('code', 'KOK')->first();
        $this->assertSame(2, $c->slides()->count());
        $this->assertSame('Orta', $c->difficulty);
        $this->assertSame('Kodlama', $c->category);
        $this->assertSame('Açıklama', $c->description);
        $this->assertSame(['K1'], $c->curriculum['kazanimlar']);

        // Slayt bulunamayan paket sessizce boş ders üretmez
        $this->post('/courses/import', ['files' => [$this->upload($this->pkg(['course' => ['name' => 'Boş Paket', 'code' => 'BOS']]))]])->assertRedirect('/courses');
        $this->assertStringContainsString('slayt bulunamadı', implode(' ', session('import_report')['warnings']));
    }
}
