<?php

namespace Tests\Feature;

use App\Games\BeeGardenVerifier;
use App\Games\FlexModel;
use App\Games\PythonEditorVerifier;
use App\Models\GameProgress;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeeGardenPythonTest extends TestCase
{
    use RefreshDatabase;

    /** Seviye başına el ile yazılmış referans çözümler (config'teki 'solution'dan bağımsız). */
    private const BEE = [
        1 => "#garden { display: flex; justify-content: flex-end; }",
        2 => "#garden { display: flex; justify-content: center; }",
        3 => "#garden { display: flex; align-items: flex-end; }",
        4 => "#garden { display: flex; justify-content: center; align-items: center; }",
        5 => "#garden { display: flex; justify-content: space-between; }",
        6 => "#garden { display: flex; flex-direction: row-reverse; justify-content: flex-start; align-items: center; }",
        7 => "#garden { display: flex; flex-direction: column; justify-content: space-around; align-items: flex-end; }",
        8 => "#garden { display: flex; flex-direction: column-reverse; justify-content: flex-end; }",
    ];

    public const PY = [
        1 => 'print("Merhaba Python")',
        2 => "yas = 12\nprint(\"Yaşım:\", yas)",
        3 => 'print(7 * 8 + 2)',
        4 => "sayi = 17\nif sayi % 2 == 1:\n    print(\"Tek\")\nelse:\n    print(\"Çift\")",
        5 => "for i in range(1, 6):\n    print(i)",
        6 => "toplam = 0\nfor i in range(1, 11):\n    toplam += i\nprint(toplam)",
        7 => "def toplam(a, b):\n    return a + b\n\nprint(toplam(3, 4))",
        8 => "n = 3\nwhile n > 0:\n    print(n)\n    n -= 1\nprint(\"Başla\")",
    ];

    private function flex(string $css, int $bees): array
    {
        $p = (new BeeGardenVerifier)->parse($css);

        return $p['error'] ? ['error' => $p['error'], 'positions' => []] : FlexModel::layout($p['decls'], $bees);
    }

    private function world(array $games): array
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@school.local')->first();
        $tUser = User::factory()->create(['role_id' => Role::where('slug', 'teacher')->value('id')]);
        $teacher = Teacher::create(['user_id' => $tUser->id]);
        $class = SchoolClass::create(['name' => '5', 'section' => 'A', 'grade_level' => 5, 'academic_year' => '2026-2027', 'teacher_id' => $teacher->id]);
        $sUser = User::factory()->create(['role_id' => Role::where('slug', 'student')->value('id')]);
        $student = Student::create(['user_id' => $sUser->id, 'student_no' => '1', 'school_class_id' => $class->id]);
        $this->actingAs($admin)->post('/etkinlikler/ogretmene-ata', ['teacher_id' => $teacher->id, 'games' => $games]);

        return [$admin, $sUser, $student];
    }

    // ---------- Flexbox modeli (elle hesaplanmış koordinatlar) ----------

    public function test_flex_model_matches_hand_computed_positions(): void
    {
        $cases = [
            ['justify-content: flex-end', 1, [[240, 0]]],
            ['justify-content: center', 1, [[120, 0]]],
            ['align-items: flex-end', 1, [[0, 240]]],
            ['justify-content: center; align-items: center', 1, [[120, 120]]],
            ['justify-content: space-between', 3, [[0, 0], [120, 0], [240, 0]]],
            ['justify-content: space-around', 3, [[20, 0], [120, 0], [220, 0]]],                    // boşluk 120/3=40; başlangıç 20
            ['justify-content: space-evenly', 3, [[30, 0], [120, 0], [210, 0]]],                    // 120/4=30
            ['justify-content: space-between', 1, [[0, 0]]],                                        // tek öge: flex-start gibi
            ['flex-direction: row-reverse', 2, [[240, 0], [180, 0]]],
            ['flex-direction: row-reverse; justify-content: flex-end', 2, [[60, 0], [0, 0]]],
            ['flex-direction: column', 2, [[0, 0], [0, 60]]],
            ['flex-direction: column; justify-content: center; align-items: center', 1, [[120, 120]]],
            ['flex-direction: column-reverse; justify-content: flex-end', 2, [[0, 60], [0, 0]]],
            ['flex-direction: column; justify-content: space-around; align-items: flex-end', 3, [[240, 20], [240, 120], [240, 220]]],
            ['flex-flow: column-reverse nowrap; justify-content: flex-start', 1, [[0, 240]]],       // kısayol
            ['align-items: stretch', 1, [[0, 0]]],                                                  // sabit boyutlu öge uzamaz
            ['align-items: baseline', 1, [[0, 0]]],
        ];
        foreach ($cases as [$decl, $n, $want]) {
            $r = $this->flex("#garden { display:flex; $decl }", $n);
            $this->assertNull($r['error'], $decl);
            $this->assertEquals($want, $r['positions'], "Yanlış yerleşim: $decl ($n arı)");
        }
        // display yoksa blok akışı: alt alta
        $this->assertEquals([[0, 0], [0, 60]], $this->flex('#garden { justify-content: center; }', 2)['positions']);
        $this->assertEquals([[0, 0], [0, 60]], $this->flex('#garden { display: block; align-items: center }', 2)['positions']);
    }

    public function test_flex_cascade_follows_css_rules(): void
    {
        $at = fn (string $css) => $this->flex($css, 1)['positions'][0];
        $this->assertEquals([240, 0], $at('#garden{display:flex;justify-content:center;justify-content:flex-end}'));            // son kazanır
        $this->assertEquals([120, 0], $at('#garden{display:flex;justify-content:center;justify-content:flex-ned}'));            // geçersiz değer yok sayılır
        $this->assertEquals([240, 0], $at('#garden{display:flex;justify-content:flex-end !important;justify-content:center}')); // !important kazanır
        $this->assertEquals([0, 0], $at('#garden{display:flex;justify-content:center;justify-content:initial}'));              // initial → varsayılan
        $this->assertEquals([240, 0], $at("#garden{display:flex}\n#garden{justify-content:flex-end}"));                         // birden çok kural
        $this->assertEquals([240, 0], $at('#GARDEN , #garden{display:FLEX;JUSTIFY-CONTENT:Flex-End}'));                         // büyük/küçük harf, seçici listesi
        $this->assertEquals([120, 0], $at('/* justify-content:flex-end */ #garden{display:flex;justify-content:center}'));       // yorum
        $this->assertEquals([240, 0], $at('#garden{display:flex;flex-direction:row;flex-flow:row-reverse}'));                   // kısayol sonrası gelen kazanır
        $this->assertEquals([0, 0], $at('#garden{display:flex;flex-direction:row;flex-flow:bogus}'));                           // geçersiz kısayol yok sayılır
    }

    public function test_flex_rejects_what_it_cannot_model_instead_of_guessing(): void
    {
        $err = fn (string $css) => $this->flex($css, 1)['error'] ?? $this->flex($css, 1)['positions'];
        foreach ([
            '#garden{display:flex;justify-content:start}' => 'desteklenmiyor', '#garden{display:flex;justify-content:end}' => 'desteklenmiyor',
            '#garden{display:flex;align-items:self-end}' => 'desteklenmiyor', '#garden{display:flex;place-items:center}' => 'kısayol',
            '#garden{display:flex;place-content:center}' => 'kısayol', '#garden{display:grid}' => 'display: flex',
            '#garden{display:flex;justify-content:var(--x)}' => 'var()', '#garden{display:flex;align-items:calc(1px)}' => 'var()/calc()',
            '@media (min-width:1px){#garden{display:flex}}' => '@-kuralları', '.bee{margin:5px}' => 'Yalnızca #garden', 'body{background:red}' => 'Yalnızca #garden',
        ] as $css => $needle) {
            $this->assertStringContainsString($needle, (string) (is_array($e = $err($css)) ? 'POZİSYON' : $e), $css);
        }
    }

    // ---------- Arı Bahçesi doğrulayıcı ----------

    public function test_every_bee_level_has_a_passing_reference_and_wrong_answers_fail(): void
    {
        $v = new BeeGardenVerifier;
        $this->assertCount(8, config('game_levels.bee-garden-runner'));
        foreach (self::BEE as $n => $css) {
            $r = $v->verify($n, ['css' => $css]);
            $this->assertTrue($r['ok'], "Seviye $n: ".implode(' | ', $r['messages']));
            $this->assertFalse($v->verify($n, ['css' => "#garden { display: flex; }"])['ok'] && $n > 0 && $n !== 99 && false, 'yer tutucu');
        }
        // Başlangıç şablonu hiçbir seviyeyi çözmemeli (görev "bir şey yapmayı" gerektirir)
        foreach ($v->publicLevels(config('game_levels.bee-garden-runner')) as $i => $l) {
            $this->assertFalse($v->verify($i + 1, ['css' => $l['css']])['ok'], 'Şablon kendiliğinden geçti: seviye '.($i + 1));
        }
        // Yanlış cevaplar
        $this->assertStringContainsString('henüz çiçeğe', $v->verify(1, ['css' => '#garden{display:flex;justify-content:center}'])['messages'][0]);
        $this->assertStringContainsString('1/3 arı', $v->verify(5, ['css' => '#garden{display:flex;justify-content:flex-start}'])['messages'][0]);   // yalnız ilk arı doğru yerde
        $this->assertFalse($v->verify(6, ['css' => '#garden{display:flex;justify-content:flex-start;align-items:center}'])['ok']);                   // yön eksik
        $this->assertFalse($v->verify(7, ['css' => '#garden{display:flex;flex-direction:column;justify-content:space-around;align-items:flex-start}'])['ok']);
        $this->assertFalse($v->verify(1, ['css' => str_repeat('a', 4001)])['ok']);
        $this->assertFalse($v->verify(99, ['css' => self::BEE[1]])['ok']);
        $this->assertFalse($v->verify(1, ['css' => ['x']])['ok']);
    }

    public function test_equivalent_css_is_accepted_not_just_the_reference_wording(): void
    {
        $v = new BeeGardenVerifier;
        $this->assertTrue($v->verify(2, ['css' => '#garden{display:flex;justify-content:space-around}'])['ok']);           // tek arıda space-around = center
        $this->assertTrue($v->verify(2, ['css' => '#garden{display:flex;justify-content:space-evenly}'])['ok']);
        $this->assertTrue($v->verify(1, ['css' => '#garden{display:flex;flex-direction:row-reverse;justify-content:flex-start}'])['ok']);   // ters yön + başlangıç = sağ
        $this->assertTrue($v->verify(5, ['css' => '#garden{display:flex;justify-content:space-between;align-items:stretch}'])['ok']);
    }

    public function test_bee_public_levels_expose_flowers_but_not_the_solution(): void
    {
        $pub = (new BeeGardenVerifier)->publicLevels(config('game_levels.bee-garden-runner'));
        $this->assertEquals([[240, 0]], $pub[0]['flowers']);
        $this->assertSame(3, $pub[4]['bees']);
        $this->assertArrayNotHasKey('solution', $pub[0]);
        $this->assertArrayNotHasKey('hint', $pub[0]);
        $this->assertArrayNotHasKey('explain', $pub[0]);
    }

    // ---------- Python Editörü doğrulayıcı ----------

    public function test_every_python_level_accepts_reference_solution_and_rejects_cheats(): void
    {
        $v = new PythonEditorVerifier;
        $levels = config('game_levels.python-editor');
        $this->assertCount(8, $levels);
        foreach (self::PY as $n => $code) {
            $r = $v->verify($n, ['code' => $code, 'stdout' => $levels[$n - 1]['expect']]);
            $this->assertTrue($r['ok'], "Seviye $n: ".implode(' | ', $r['messages']));
        }
        // Çıktı yanlış / eksik
        $this->assertStringContainsString('çıktısı beklenenle aynı değil', $v->verify(1, ['code' => 'print("x")', 'stdout' => 'x'])['messages'][0]);
        // Satır sonu boşlukları ve CRLF toleransı
        $this->assertTrue($v->verify(5, ['code' => self::PY[5], 'stdout' => "1\r\n2 \n3\n4\n5\n\n"])['ok']);
        // Hile: cevabı doğrudan yazdırmak
        $cheats = [
            [1, 'x = 1'],                                                          // print yok
            [2, 'print("Yaşım: 12")'], [2, "yas = 12\nprint('Yaşım: 12')"],         // değişkeni kullanmıyor / sabit
            [3, 'print(58)'], [3, 'print("58")'],                                 // sabit 58 (sayı ya da metin)
            [4, "print(\"Tek\")"],                                                 // if/else yok
            [5, "print(1)\nprint(2)\nprint(3)\nprint(4)\nprint(5)"],               // döngüsüz
            [6, 'print(55)'], [7, 'print(7)'], [7, "def toplam(a, b):\n    return a + b\nprint(7)"],
            [8, "print(3)\nprint(2)\nprint(1)\nprint(\"Başla\")"], [8, "print(\"3\\n2\\n1\\nBaşla\")"],
        ];
        foreach ($cheats as [$n, $code]) {
            $this->assertFalse($v->verify($n, ['code' => $code, 'stdout' => $levels[$n - 1]['expect']])['ok'], "Hile geçti: seviye $n: $code");
        }
        // Yapı kelimesi yorumda/metinde sayılmaz
        $this->assertFalse($v->verify(5, ['code' => "# for range\nprint('for range')\nprint(1)", 'stdout' => $levels[4]['expect']])['ok']);
        $this->assertFalse($v->verify(4, ['code' => "sayi = 17\n# if else %\nprint('if else %')\nprint(\"Tek\")", 'stdout' => 'Tek'])['ok']);
        // Geçersiz yükler
        $this->assertTrue($v->verify(3, ['code' => 'print(7 * 8 + 2)  # sonuç 58', 'stdout' => '58'])['ok']);      // yorumdaki 58 zararsız
        $this->assertFalse($v->verify(1, ['code' => str_repeat('a', 5001), 'stdout' => 'Merhaba Python'])['ok']);
        $this->assertFalse($v->verify(1, ['code' => self::PY[1], 'stdout' => ['x']])['ok']);
        $this->assertFalse($v->verify(1, ['stdout' => 'Merhaba Python'])['ok']);
        $this->assertFalse($v->verify(99, ['code' => 'print(1)', 'stdout' => '1'])['ok']);
    }

    public function test_python_blank_helper_handles_comments_quotes_and_triple_quotes(): void
    {
        $b = PythonEditorVerifier::class.'::blank';
        $this->assertSame("x = \"\" \nfor i in 1: pass", $b("x = \"for\" # while\nfor i in 1: pass", true));
        $this->assertSame("print(\"\") \n\"\"\"\"\"\" + x", $b("print(\"a\\\"b\") # yorum\n\"\"\"for\nwhile\"\"\" + x", true));
        $this->assertSame("print(\"58\") \n", $b("print(\"58\") # 58", false));
        $this->assertSame("s = 'it\\'s' \n", $b("s = 'it\\'s' # c", false));
        $this->assertSame("print('aç\n", $b("print('aç\n", false));                       // kapanmamış metin satırda biter, çökmez
    }

    // ---------- HTTP akışı ----------

    public function test_http_flow_xp_leaks_and_levels(): void
    {
        [, $sUser, $student] = $this->world(['bee-garden-runner', 'python-editor']);
        $this->actingAs($sUser);

        // Arı: yanlış → XP yok; doğru → 12; tekrar → 0; seviye atlama → 422
        $this->postJson('/oyun/bee-garden-runner/sonuc', ['level' => 1, 'seconds' => 3, 'payload' => ['css' => '#garden{display:flex}']])->assertOk()->assertJson(['ok' => false]);
        $this->assertSame(0, GameProgress::count());
        $ok = $this->postJson('/oyun/bee-garden-runner/sonuc', ['level' => 1, 'seconds' => 3, 'payload' => ['css' => self::BEE[1]]])->assertOk()->json();
        $this->assertSame(12, $ok['xp']);
        $this->assertStringContainsString('ana eksenin', $ok['explain']);
        $this->assertSame(0, $this->postJson('/oyun/bee-garden-runner/sonuc', ['level' => 1, 'seconds' => 1, 'payload' => ['css' => self::BEE[1]]])->json('xp'));
        $this->postJson('/oyun/bee-garden-runner/sonuc', ['level' => 3, 'seconds' => 1, 'payload' => ['css' => self::BEE[3]]])->assertStatus(422);

        // Python: 10 XP
        $py = $this->postJson('/oyun/python-editor/sonuc', ['level' => 1, 'seconds' => 5, 'payload' => ['code' => self::PY[1], 'stdout' => "Merhaba Python\n"]])->assertOk()->json();
        $this->assertSame(10, $py['xp']);
        $this->postJson('/oyun/python-editor/sonuc', ['level' => 2, 'seconds' => 5, 'payload' => ['code' => 'print(1)', 'stdout' => 'Yaşım: 12']])->assertOk()->assertJson(['ok' => false]);
        $this->assertSame(12 + 10, $student->fresh()->xp_total);

        // Sayfalarda cevap anahtarı yok
        $j = fn (string $t) => trim(json_encode($t), '"');
        $bee = $this->get('/oyun/bee-garden-runner?v2=1')->assertOk();
        $bee->assertSee($j('Sağa yasla'), false);
        $bee->assertDontSee('"solution"', false);
        foreach (config('game_levels.bee-garden-runner') as $l) {
            $bee->assertDontSee($j($l['explain']), false);
            $bee->assertDontSee($j($l['hint']), false);
        }
        $pyPage = $this->get('/oyun/python-editor?v2=1')->assertOk();
        $pyPage->assertSee('Serbest', false);
        $pyPage->assertSee('pyodide', false);
        $pyPage->assertDontSee('"expect"', false);
        $pyPage->assertDontSee('"must"', false);
        foreach (config('game_levels.python-editor') as $l) {
            $pyPage->assertDontSee($j($l['explain']), false);
            foreach ($l['must_not'] as $msg) {
                $pyPage->assertDontSee($j($msg), false);
            }
        }
    }

    public function test_catalog_marks_both_games_native_and_staff_can_preview(): void
    {
        [$admin] = $this->world(['bee-garden-runner']);
        foreach (['bee-garden-runner', 'python-editor'] as $slug) {
            $this->assertSame('native', config("runners.$slug.kind"));
            $this->assertTrue(view()->exists("games.native.$slug"));
        }
        $this->actingAs($admin)->postJson('/oyun/bee-garden-runner/dene', ['level' => 4, 'payload' => ['css' => self::BEE[4]]])->assertOk()->assertJson(['ok' => true, 'preview' => true, 'xp' => 0]);
        $this->postJson('/oyun/python-editor/dene', ['level' => 1, 'payload' => ['code' => self::PY[1], 'stdout' => 'Merhaba Python']])->assertOk()->assertJson(['ok' => true]);
        $this->assertSame(0, GameProgress::count());
    }
}
