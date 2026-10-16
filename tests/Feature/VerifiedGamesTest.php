<?php

namespace Tests\Feature;

use App\Games\LightbotVerifier;
use App\Games\SilentTeacherVerifier;
use App\Games\WebLabVerifier;
use App\Models\GameProgress;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifiedGamesTest extends TestCase
{
    use RefreshDatabase;

    private const LIGHTBOT_SOLUTIONS = [
        1 => ['main' => ['F', 'F', 'F', 'X'], 'proc' => []],
        2 => ['main' => ['F', 'F', 'F', 'R', 'F', 'F', 'X'], 'proc' => []],
        3 => ['main' => ['R', 'F', 'F', 'F', 'L', 'F', 'F', 'F', 'L', 'F', 'X'], 'proc' => []],
        4 => ['main' => ['P', 'P'], 'proc' => ['F', 'F', 'X']],
        5 => ['main' => ['P', 'P', 'P'], 'proc' => ['F', 'F', 'F', 'F', 'X', 'R']],
        6 => ['main' => ['P', 'X', 'R', 'P', 'R', 'P', 'X'], 'proc' => ['F', 'F', 'F', 'F']],
    ];

    private const WEBLAB_SOLUTIONS = [
        1 => ['html' => '<h1>Merhaba dünya</h1>', 'css' => ''],
        2 => ['html' => '<h1>Sayfam</h1><p>Bu bir paragraf metnidir.</p>', 'css' => ''],
        3 => ['html' => '<ul><li>a</li><li>b</li><li>c</li></ul>', 'css' => ''],
        4 => ['html' => '<a href="https://example.com">Git</a>', 'css' => ''],
        5 => ['html' => '<img src="a.png" alt="Bir resim">', 'css' => ''],
        6 => ['html' => '<h1>Renkli</h1>', 'css' => 'h1 { color: #FF0000; }'],
        7 => ['html' => '<p>Merhaba</p>', 'css' => "p { font-size: 20px; background-color: yellow }"],
        8 => ['html' => '<div class="kutu">x</div>', 'css' => '/* kutu */ .kutu { border: 1px solid black; padding: 10px; }'],
    ];

    private function world(array $games = []): array
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@school.local')->first();
        $tUser = User::factory()->create(['role_id' => Role::where('slug', 'teacher')->value('id')]);
        $teacher = Teacher::create(['user_id' => $tUser->id]);
        $class = SchoolClass::create(['name' => '5', 'section' => 'A', 'grade_level' => 5, 'academic_year' => '2026-2027', 'teacher_id' => $teacher->id]);
        $sUser = User::factory()->create(['role_id' => Role::where('slug', 'student')->value('id')]);
        $student = Student::create(['user_id' => $sUser->id, 'student_no' => '1', 'school_class_id' => $class->id]);
        if ($games) {
            $this->actingAs($admin)->post('/etkinlikler/ogretmene-ata', ['teacher_id' => $teacher->id, 'games' => $games]);
        }

        return [$admin, $sUser, $student];
    }

    // ---------- Lightbot ----------

    public function test_every_lightbot_level_is_solvable_and_limits_force_abstraction(): void
    {
        $v = new LightbotVerifier;
        $levels = config('game_levels.lightbot-runner');
        $this->assertCount(6, $levels);
        foreach (self::LIGHTBOT_SOLUTIONS as $n => $sol) {
            $r = $v->verify($n, $sol);
            $this->assertTrue($r['ok'], "Seviye $n cozulemedi: ".implode(',', $r['messages']));
        }

        // Prosedursuz uzun cozum, prosedur seviyesinde kabul edilmez (limit).
        $naive = ['main' => ['F', 'F', 'X', 'F', 'F', 'X'], 'proc' => []];
        $this->assertFalse($v->verify(4, $naive)['ok']);
        $this->assertStringContainsString('sınır', $v->verify(4, $naive)['messages'][0]);
    }

    public function test_lightbot_rejects_crash_missing_light_bad_commands_and_proc_misuse(): void
    {
        $v = new LightbotVerifier;
        $this->assertStringContainsString('duvara', $v->verify(1, ['main' => ['L', 'F'], 'proc' => []])['messages'][0]);        // kuzeye -> kenar
        $this->assertStringContainsString('yanmadı', $v->verify(1, ['main' => ['F', 'F', 'F'], 'proc' => []])['messages'][0]);  // X yok
        $this->assertFalse($v->verify(1, ['main' => ['F', 'F', 'F', 'Z'], 'proc' => []])['ok']);
        $this->assertFalse($v->verify(1, ['main' => ['P'], 'proc' => ['F']])['ok']);                                            // proc_max 0
        $this->assertFalse($v->verify(4, ['main' => ['P'], 'proc' => ['P']])['ok']);                                            // ic ice P yok
        $this->assertFalse($v->verify(99, self::LIGHTBOT_SOLUTIONS[1])['ok']);
        $this->assertFalse($v->verify(1, ['main' => 'F'])['ok']);
        // Duvar: seviye 3'te dogrudan dogu = duvar
        $this->assertStringContainsString('duvara', $v->verify(3, ['main' => ['F', 'F', 'F', 'X'], 'proc' => []])['messages'][0]);
    }

    // ---------- Silent Teacher ----------

    public function test_silent_teacher_answers_and_normalization(): void
    {
        $v = new SilentTeacherVerifier;
        $levels = config('game_levels.silent-teacher-runner');
        $this->assertCount(12, $levels);
        foreach ($levels as $i => $l) {
            $this->assertTrue($v->verify($i + 1, ['answer' => $l['output']])['ok'], 'Seviye '.($i + 1));
        }
        $this->assertTrue($v->verify(1, ['answer' => "Merhaba  \r\nDünya\n\n"])['ok']);          // satir sonu bosluklari / CRLF / bos satir
        $this->assertFalse($v->verify(1, ['answer' => "merhaba\nDünya"])['ok']);                 // buyuk-kucuk harf farki
        $this->assertFalse($v->verify(4, ['answer' => 'Merhaba Ada x'])['ok']);
        $this->assertSame('Boşluğa dikkat et.', $v->verify(4, ['answer' => 'MerhabaAda'])['messages'][0]);
        $this->assertFalse($v->verify(0, ['answer' => 'x'])['ok']);
        $this->assertFalse($v->verify(1, ['answer' => ['a']])['ok']);
        $this->assertFalse($v->verify(1, ['answer' => str_repeat('a', 501)])['ok']);

        // Beklenen ciktilar kod-ciktisi olarak gercekten tutarli mi? Python varsa calistirip karsilastir.
        $canExec = function_exists('shell_exec') && ! in_array('shell_exec', array_map('trim', explode(',', (string) ini_get('disable_functions'))), true);
        $py = $canExec ? trim((string) shell_exec('command -v python3')) : '';
        if ($py) {
            foreach ($levels as $i => $l) {
                $out = shell_exec('printf %s '.escapeshellarg($l['code']).' | '.$py.' - 2>&1');
                $this->assertSame(SilentTeacherVerifier::normalize($l['output']), SilentTeacherVerifier::normalize((string) $out), 'Seviye '.($i + 1).' beklenen cikti Python ile uyusmuyor');
            }
        }
    }

    // ---------- Web Atolyesi ----------

    public function test_every_weblab_level_has_a_passing_solution_and_empty_fails(): void
    {
        $v = new WebLabVerifier;
        $this->assertCount(8, config('game_levels.web-lab-runner'));
        foreach (self::WEBLAB_SOLUTIONS as $n => $sol) {
            $r = $v->verify($n, $sol);
            $this->assertTrue($r['ok'], "Seviye $n: ".implode(' | ', $r['messages']));
            $starter = config('game_levels.web-lab-runner')[$n - 1];
            $this->assertFalse($v->verify($n, ['html' => '', 'css' => ''])['ok'], "Seviye $n bos kodla gecmemeli");
        }
    }

    public function test_weblab_css_parsing_rules(): void
    {
        $v = new WebLabVerifier;
        $h = '<h1>Renkli</h1>';
        $this->assertTrue($v->verify(6, ['html' => $h, 'css' => 'h1,p{color:RED}'])['ok']);                       // secici listesi + buyuk harf
        $this->assertTrue($v->verify(6, ['html' => $h, 'css' => 'h1{color:rgb(255, 0, 0) !important}'])['ok']);   // bosluk + important
        $this->assertTrue($v->verify(6, ['html' => '<style>h1{color:red}</style>'.$h, 'css' => ''])['ok']);       // <style> bloku
        $this->assertFalse($v->verify(6, ['html' => $h, 'css' => 'h1{color:blue}'])['ok']);
        $this->assertFalse($v->verify(6, ['html' => $h, 'css' => 'h1{color:red} h1{color:blue}'])['ok']);          // sonraki kural kazanir
        $this->assertFalse($v->verify(6, ['html' => $h, 'css' => '/* h1{color:red} */'])['ok']);                   // yorum satiri sayilmaz
        $this->assertFalse($v->verify(6, ['html' => $h, 'css' => 'h2{color:red}'])['ok']);
        $this->assertFalse($v->verify(6, ['html' => $h, 'css' => 'h1{colour:red}'])['ok']);
    }

    public function test_weblab_html_checks_and_input_limits(): void
    {
        $v = new WebLabVerifier;
        $this->assertFalse($v->verify(1, ['html' => '<h1>Selam</h1>', 'css' => ''])['ok']);                        // metin yok
        $this->assertTrue($v->verify(1, ['html' => '<H1>MERHABA</H1>', 'css' => ''])['ok']);                       // buyuk harf
        $this->assertFalse($v->verify(3, ['html' => '<ul><li>1</li><li>2</li></ul>', 'css' => ''])['ok']);         // 2 madde
        $this->assertFalse($v->verify(4, ['html' => '<a href="ftp://x">Git</a>', 'css' => ''])['ok']);             // http degil
        $this->assertFalse($v->verify(4, ['html' => '<a href="http://x"></a>', 'css' => ''])['ok']);               // bos baglanti
        $this->assertFalse($v->verify(5, ['html' => '<img src="a.png">', 'css' => ''])['ok']);                     // alt yok
        $this->assertFalse($v->verify(1, ['html' => str_repeat('a', 20001), 'css' => ''])['ok']);                  // boyut
        $this->assertFalse($v->verify(1, ['html' => ['x'], 'css' => ''])['ok']);
        $this->assertTrue($v->verify(1, ['html' => '<script>alert(1)</script><h1>merhaba</h1><p', 'css' => ''])['ok']); // bozuk HTML cokmez, script calistirilmaz
    }

    // ---------- HTTP akisi ----------

    public function test_server_decides_xp_wrong_answers_give_none_and_levels_stay_sequential(): void
    {
        [, $sUser, $student] = $this->world(['silent-teacher-runner', 'lightbot-runner', 'web-lab-runner']);
        $this->actingAs($sUser);

        $wrong = $this->postJson('/oyun/silent-teacher-runner/sonuc', ['level' => 1, 'seconds' => 3, 'payload' => ['answer' => 'yanlis']])->assertOk()->json();
        $this->assertFalse($wrong['ok']);
        $this->assertSame(0, GameProgress::count());
        $this->assertSame(0, $student->fresh()->xp_total);

        $ok = $this->postJson('/oyun/silent-teacher-runner/sonuc', ['level' => 1, 'seconds' => 3, 'payload' => ['answer' => "Merhaba\nDünya"]])->assertOk()->json();
        $this->assertTrue($ok['ok']);
        $this->assertSame(10, $ok['xp']);
        $this->assertStringContainsString('print', $ok['explain']);                                   // aciklama yalniz cozumden sonra
        $again = $this->postJson('/oyun/silent-teacher-runner/sonuc', ['level' => 1, 'seconds' => 1, 'payload' => ['answer' => "Merhaba\nDünya"]])->json();
        $this->assertTrue($again['repeat']);
        $this->assertSame(0, $again['xp']);                                                           // tekrar XP yok

        // 2. seviyeyi dogru cevaplasa da 1 bitmeden 3. kabul edilmez.
        $this->postJson('/oyun/silent-teacher-runner/sonuc', ['level' => 3, 'seconds' => 1, 'payload' => ['answer' => "6\n40"]])->assertStatus(422);

        // Lightbot / Web Atolyesi XP degerleri
        $lb = $this->postJson('/oyun/lightbot-runner/sonuc', ['level' => 1, 'seconds' => 4, 'payload' => self::LIGHTBOT_SOLUTIONS[1]])->json();
        $this->assertSame(12, $lb['xp']);
        $wl = $this->postJson('/oyun/web-lab-runner/sonuc', ['level' => 1, 'seconds' => 4, 'payload' => self::WEBLAB_SOLUTIONS[1]])->json();
        $this->assertSame(12, $wl['xp']);
        $this->assertSame(10 + 12 + 12, $student->fresh()->xp_total);

        // Istemci "bitirdim" diyemez: payload olmadan gonderilen sonuc reddedilir.
        $this->postJson('/oyun/lightbot-runner/sonuc', ['level' => 2, 'seconds' => 1, 'score' => 999])->assertOk()->assertJson(['ok' => false]);
        $this->assertSame(0, GameProgress::where('game_slug', 'lightbot-runner')->where('level', 2)->count());
    }

    public function test_answer_keys_never_reach_the_browser(): void
    {
        [, $sUser] = $this->world(['silent-teacher-runner', 'lightbot-runner', 'web-lab-runner']);
        $this->actingAs($sUser);

        $j = fn (string $text) => trim(json_encode($text), '"');   // @json unicode'u \uXXXX yazar; sizinti kontrolu ayni bicimle yapilmali

        $st = $this->get('/oyun/silent-teacher-runner')->assertOk();
        $st->assertSee('print(', false);                     // kod gorunur
        $st->assertSee($j('Metin birleştirme'), false);      // seviye basligi gorunur
        foreach (config('game_levels.silent-teacher-runner') as $l) {
            $st->assertDontSee($j($l['explain']), false);    // aciklamalar gizli
        }
        $st->assertDontSee('"output"', false);
        $st->assertDontSee('"explain"', false);
        $st->assertDontSee($j('Merhaba Ada'), false);        // beklenen cikti (kodda yalniz "Merhaba " + ad var)

        $wl = $this->get('/oyun/web-lab-runner')->assertOk();
        $wl->assertSee($j('Sınıf (class)'), false);
        foreach (config('game_levels.web-lab-runner') as $l) {
            foreach ($l['checks'] as $c) {
                $wl->assertDontSee($j($c['msg']), false);    // kontrol mesajlari gizli
            }
        }
        $wl->assertDontSee('"checks"', false);

        $this->get('/oyun/lightbot-runner')->assertOk()->assertSee('Kare turu', false);
    }

    public function test_game_access_still_enforced_and_staff_preview_does_not_record(): void
    {
        [$admin, $sUser] = $this->world(['web-lab-runner']);
        $this->actingAs($sUser)->get('/oyun/lightbot-runner')->assertForbidden();                                // acilmamis
        $this->postJson('/oyun/lightbot-runner/sonuc', ['level' => 1, 'seconds' => 1, 'payload' => self::LIGHTBOT_SOLUTIONS[1]])->assertForbidden();
        $this->postJson('/oyun/lightbot-runner/dene', ['level' => 1, 'payload' => self::LIGHTBOT_SOLUTIONS[1]])->assertForbidden();   // ogrenci personel ucunu kullanamaz

        $this->actingAs($admin)->get('/oyun/lightbot-runner')->assertOk();
        $this->postJson('/oyun/lightbot-runner/dene', ['level' => 1, 'payload' => self::LIGHTBOT_SOLUTIONS[1]])->assertOk()->assertJson(['ok' => true, 'preview' => true, 'xp' => 0]);
        $this->postJson('/oyun/lightbot-runner/dene', ['level' => 1, 'payload' => ['main' => ['F'], 'proc' => []]])->assertOk()->assertJson(['ok' => false]);
        $this->assertSame(0, GameProgress::count());
        $this->postJson('/oyun/keyboard-race/dene', ['level' => 1, 'payload' => []])->assertNotFound();           // dogrulayicisi olmayan oyun
    }

    public function test_catalog_marks_new_games_as_ready(): void
    {
        [$admin] = $this->world();
        foreach (['silent-teacher-runner', 'lightbot-runner', 'web-lab-runner'] as $slug) {
            $this->assertSame('native', config("runners.$slug.kind"));
            $this->assertTrue(view()->exists("games.native.$slug"));
        }
    }
}
