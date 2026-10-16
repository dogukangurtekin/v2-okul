<?php

namespace Tests\Feature;

use App\Models\LiveSession;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\GameProgressService;
use App\Services\LiveSessionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveSessionTest extends TestCase
{
    use RefreshDatabase;

    private function world(int $students = 2): array
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@school.local')->first();
        $class = SchoolClass::create(['name' => '5', 'section' => 'A', 'grade_level' => 5, 'academic_year' => '2026-2027']);
        $list = [];
        for ($i = 1; $i <= $students; $i++) {
            $u = User::factory()->create(['role_id' => Role::where('slug', 'student')->value('id')]);
            $list[] = [$u, Student::create(['user_id' => $u->id, 'student_no' => (string) $i, 'school_class_id' => $class->id])];
        }

        return [$admin, $class, $list];
    }

    private function quiz(User $admin): Quiz
    {
        $this->actingAs($admin)->post('/quizzes', ['title' => 'T', 'questions' => [
            ['text' => '1+1?', 'options' => "1\n2\n3", 'correct' => 1, 'duration' => 20, 'xp' => 10, 'double' => 1],
            ['text' => '2+2?', 'options' => "4\n5", 'correct' => 0, 'duration' => 20, 'xp' => 10],
        ]])->assertSessionHasNoErrors();

        return Quiz::first();
    }

    public function test_quiz_full_flow_scores_by_speed_and_awards_xp_once(): void
    {
        [$admin, , [[$u1, $s1], [$u2, $s2]]] = $this->world();
        $quiz = $this->quiz($admin);
        $this->assertCount(2, $quiz->questions);

        $this->actingAs($admin)->post('/canli', ['type' => 'quiz', 'quiz_id' => $quiz->id])->assertRedirect();
        $session = LiveSession::first();

        $this->actingAs($u1)->post('/ogrenci/canli', ['code' => strtolower($session->join_code)])->assertRedirect();
        $this->actingAs($u2)->post('/ogrenci/canli', ['code' => $session->join_code])->assertRedirect();
        $this->actingAs($admin)->post("/canli/{$session->id}/baslat");

        // Dogru cevap gizli, soru acik.
        $snap = $this->actingAs($u1)->getJson("/ogrenci/canli/{$session->id}/durum")->assertOk()->json();
        $this->assertArrayNotHasKey('correct', $snap);
        $this->assertSame('1+1?', $snap['question']['text']);

        $r = $this->postJson("/ogrenci/canli/{$session->id}/cevap", ['question' => 0, 'answer' => 1])->assertOk()->json();
        $this->assertTrue($r['ok']);
        $this->assertGreaterThanOrEqual(10, $r['points']); // 2x XP: 10*(0.5..1)*2
        $this->assertFalse($this->postJson("/ogrenci/canli/{$session->id}/cevap", ['question' => 0, 'answer' => 1])->json('ok')); // ikinci cevap yok
        $this->actingAs($u2)->postJson("/ogrenci/canli/{$session->id}/cevap", ['question' => 0, 'answer' => 0]);
        $this->assertFalse($this->postJson("/ogrenci/canli/{$session->id}/cevap", ['question' => 1, 'answer' => 0])->json('ok')); // yanlis soru indeksi

        $this->actingAs($admin)->post("/canli/{$session->id}/sonraki");
        $this->actingAs($admin)->post("/canli/{$session->id}/sonraki"); // son soru sonrasi biter
        $this->assertSame('finished', $session->fresh()->status);

        $xp1 = $s1->fresh()->xp_total;
        $this->assertSame($r['points'], $xp1);
        $this->assertSame(0, $s2->fresh()->xp_total);
        app(LiveSessionService::class)->finish($session); // tekrar cagri XP vermez
        $this->assertSame($xp1, $s1->fresh()->xp_total);
        $this->actingAs($admin)->get("/canli/{$session->id}/rapor")->assertOk()->assertSee($u1->name);
    }

    public function test_class_restricted_session_and_locked_lobby(): void
    {
        [$admin, $class, [[$u1]]] = $this->world(1);
        $other = SchoolClass::create(['name' => '6', 'section' => 'B', 'grade_level' => 6, 'academic_year' => '2026-2027']);
        $this->actingAs($admin)->post('/canli', ['type' => 'typing', 'text' => 'abc', 'school_class_id' => $other->id]);
        $session = LiveSession::first();

        $this->actingAs($u1)->post('/ogrenci/canli', ['code' => $session->join_code])->assertSessionHasErrors('code'); // baska sinif
        $this->assertSame(0, $session->participants()->count());
    }

    public function test_typing_race_is_server_scored_and_ranked(): void
    {
        [$admin, , [[$u1, $s1], [$u2, $s2]]] = $this->world();
        $this->actingAs($admin)->post('/canli', ['type' => 'typing', 'text' => 'merhaba dunya', 'duration' => 60]);
        $session = LiveSession::first();
        foreach ([$u1, $u2] as $u) {
            $this->actingAs($u)->post('/ogrenci/canli', ['code' => $session->join_code]);
        }
        // Baslamadan once metin sizmaz.
        $this->assertArrayNotHasKey('text', $this->actingAs($u1)->getJson("/ogrenci/canli/{$session->id}/durum")->json('config') ?? []);
        $this->actingAs($admin)->post("/canli/{$session->id}/baslat");

        $this->actingAs($u1)->postJson("/ogrenci/canli/{$session->id}/yazi", ['typed' => 'merhaba dunya']);
        $this->actingAs($u2)->postJson("/ogrenci/canli/{$session->id}/yazi", ['typed' => 'merhaba xxxxx']);

        $this->actingAs($admin)->post("/canli/{$session->id}/bitir");
        $p1 = $session->participants()->where('student_id', $s1->id)->first();
        $p2 = $session->participants()->where('student_id', $s2->id)->first();
        $this->assertSame(1, $p1->rank_no);
        $this->assertEquals(100, $p1->progress);
        $this->assertLessThan(100, (float) $p2->progress);
        $this->assertSame(30, $s1->fresh()->xp_total);
        $this->assertSame(2, $s2->fresh()->xp_total); // bitiremeyen: katilim XP'si
    }

    public function test_competition_progress_comes_from_game_progress_and_grants_play_access(): void
    {
        [$admin, , [[$u1, $s1], [$u2, $s2]]] = $this->world();
        $this->actingAs($admin)->post('/canli', ['type' => 'competition', 'game_slug' => 'keyboard-race', 'level_from' => 1, 'level_to' => 2, 'duration' => 120]);
        $session = LiveSession::first();
        foreach ([$u1, $u2] as $u) {
            $this->actingAs($u)->post('/ogrenci/canli', ['code' => $session->join_code]);
        }
        $this->actingAs($u1)->get('/oyun/keyboard-race')->assertForbidden(); // yarisma baslamadi
        $this->actingAs($admin)->post("/canli/{$session->id}/baslat");
        $this->actingAs($u1)->get('/oyun/keyboard-race')->assertOk();

        $games = app(GameProgressService::class);
        $games->record($s1, 'keyboard-race', 1, 5, 0);
        $games->record($s1, 'keyboard-race', 2, 5, 0);
        $games->record($s2, 'keyboard-race', 1, 5, 0);

        $snap = $this->actingAs($u1)->getJson("/ogrenci/canli/{$session->id}/durum")->json();
        $this->assertEquals(100, $snap['me']['progress']);
        $this->actingAs($admin)->post("/canli/{$session->id}/bitir");
        $this->assertSame(1, $session->participants()->where('student_id', $s1->id)->value('rank_no'));
        $this->assertSame(2, $session->participants()->where('student_id', $s2->id)->value('rank_no'));
    }
}
