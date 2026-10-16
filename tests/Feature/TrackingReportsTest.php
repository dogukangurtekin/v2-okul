<?php

namespace Tests\Feature;

use App\Contracts\PushSender;
use App\Models\Assignment;
use App\Models\ParentMessage;
use App\Models\PushSubscription;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\ParentReportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingReportsTest extends TestCase
{
    use RefreshDatabase;

    private function world(): array
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@school.local')->first();
        $tUser = User::factory()->create(['role_id' => Role::where('slug', 'teacher')->value('id')]);
        $teacher = Teacher::create(['user_id' => $tUser->id]);
        $mine = SchoolClass::create(['name' => '5', 'section' => 'A', 'grade_level' => 5, 'academic_year' => '2026-2027', 'teacher_id' => $teacher->id]);
        $other = SchoolClass::create(['name' => '6', 'section' => 'B', 'grade_level' => 6, 'academic_year' => '2026-2027']);
        $mk = function ($class, $phone = '0532 111 22 33') {
            $u = User::factory()->create(['role_id' => Role::where('slug', 'student')->value('id')]);

            return [$u, Student::create(['user_id' => $u->id, 'student_no' => (string) $u->id, 'school_class_id' => $class->id, 'parent_phone' => $phone])];
        };

        return [$admin, $tUser, $mk($mine), $mk($other), $mine];
    }

    public function test_student_activity_is_tracked_and_teacher_sees_only_own_class(): void
    {
        [, $tUser, [$su, $s], [, $foreign]] = $this->world();
        $this->actingAs($su)->get('/dashboard');
        $this->assertNotNull($s->fresh()->last_seen_at);
        $this->assertSame('dashboard', $s->fresh()->last_path);
        $this->assertSame(1, $s->activityCount ?? \App\Models\StudentActivityLog::where('student_id', $s->id)->count());

        $this->actingAs($tUser);
        $this->getJson('/takip/veri')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $s->id);
        $this->get("/takip/ogrenci/{$s->id}")->assertOk();
        $this->get("/takip/ogrenci/{$foreign->id}")->assertForbidden();
        $this->get("/rapor/{$foreign->id}")->assertForbidden();
    }

    public function test_force_logout_kills_session_but_allows_new_login(): void
    {
        [$admin, , [$su, $s]] = $this->world();
        $this->post('/login', ['email' => $su->email, 'password' => 'password'])->assertRedirect();
        $this->get('/dashboard')->assertOk();

        $this->travel(2)->seconds();
        $su->forceFill(['force_logout_at' => now()])->save();
        $this->app['auth']->forgetGuards(); // ayni surecte onbellekli kullanici nesnesini at
        $this->get('/dashboard')->assertRedirect('/login');

        $this->travel(3)->seconds();
        $this->post('/login', ['email' => $su->email, 'password' => 'password'])->assertRedirect();
        $this->get('/dashboard')->assertOk();
    }

    public function test_analytics_scores_and_csv(): void
    {
        [$admin, , [$su, $s], , $class] = $this->world();
        $a = Assignment::create(['type' => 'game', 'game_slug' => 'keyboard-race', 'title' => 'A', 'xp_reward' => 5, 'created_by' => $admin->id]);
        $b = Assignment::create(['type' => 'game', 'game_slug' => 'keyboard-race', 'title' => 'B', 'xp_reward' => 5, 'created_by' => $admin->id]);
        $a->classes()->sync([$class->id]);
        $b->classes()->sync([$class->id]);
        $a->submissions()->create(['student_id' => $s->id, 'completed_at' => now()]);

        $stats = app(AnalyticsService::class)->forStudents(collect([$s]))[$s->id];
        $this->assertSame(1, $stats['assignments_done']);
        $this->assertSame(2, $stats['assignments_total']);
        $this->assertSame(50, $stats['score']);

        $csv = $this->actingAs($admin)->get('/analitik/indir')->assertOk()->streamedContent();
        $this->assertStringContainsString('Başarı', $csv);
        $this->assertStringContainsString($su->name, $csv);
        $this->actingAs($admin)->get('/analitik')->assertOk()->assertSee($su->name);
    }

    public function test_parent_link_is_signed_and_phone_normalized(): void
    {
        [$admin, , [$su, $s]] = $this->world();
        $svc = app(ParentReportService::class);
        $this->assertSame('905321112233', $svc->phone('0532 111 22 33'));
        $this->assertSame('905321112233', $svc->phone('+90 (532) 111-22-33'));
        $this->assertNull($svc->phone('12345'));

        $link = $svc->link($s);
        $this->get($link)->assertOk()->assertSee('Gelişim Karnesi')->assertSee($su->name);
        $this->get("/veli/rapor/{$s->id}")->assertForbidden(); // imzasiz
        $this->get($link.'x')->assertForbidden();               // bozuk imza

        $this->actingAs($admin)->post("/rapor/{$s->id}/veli")->assertSessionHas('ok');
        $this->assertSame('logged', ParentMessage::first()->status);

        $s->update(['parent_phone' => null]);
        $this->post("/rapor/{$s->id}/veli");
        $this->assertSame('no_phone', ParentMessage::latest('id')->first()->status);
    }

    public function test_notifications_inbox_read_and_push_fanout(): void
    {
        [$admin, $tUser, [$su, $s], [$ou]] = $this->world();
        $fake = new class implements PushSender {
            public array $sent = [];
            public function send(PushSubscription $sub, array $payload): string { $this->sent[] = $payload['title']; return 'sent'; }
        };
        $this->app->instance(PushSender::class, $fake);

        $this->actingAs($su)->postJson('/push/abone', ['endpoint' => 'https://push.example/abc', 'keys' => ['p256dh' => 'k', 'auth' => 'a']])->assertOk();
        $this->assertSame(1, PushSubscription::count());

        $class = $s->schoolClass;
        $this->actingAs($tUser)->post('/bildirim-gonder', ['title' => 'Yarın sınav', 'body' => 'Hazırlanın', 'class_ids' => [$class->id]])->assertSessionHasNoErrors();
        $this->assertSame(['Yarın sınav'], $fake->sent);

        $this->actingAs($su)->get('/bildirimlerim')->assertOk()->assertSee('Yarın sınav');
        $this->assertSame(1, $su->notifications()->wherePivotNull('read_at')->count());
        $n = $su->notifications()->first();
        $this->post("/bildirimlerim/{$n->id}/oku");
        $this->assertSame(0, $su->notifications()->wherePivotNull('read_at')->count());
        $this->actingAs($ou)->post("/bildirimlerim/{$n->id}/oku")->assertNotFound(); // alici degil

        // Teacher baska sinifa bildirim gonderemez.
        $foreignClass = SchoolClass::where('name', '6')->first();
        $this->actingAs($tUser)->post('/bildirim-gonder', ['title' => 'x', 'body' => 'y', 'class_ids' => [$foreignClass->id]])->assertStatus(422);
    }

    public function test_time_ping_accumulates_bounded_seconds(): void
    {
        [, , [$su, $s]] = $this->world();
        $s->update(['last_seen_at' => now()->subSeconds(30)]);
        $this->actingAs($su)->postJson('/ogrenci/sure/ping')->assertOk();
        $this->assertGreaterThanOrEqual(29, $s->fresh()->total_seconds);
        $this->assertLessThanOrEqual(31, $s->fresh()->total_seconds);

        $s->update(['last_seen_at' => now()->subHours(5), 'total_seconds' => 0]);
        $this->app['auth']->forgetGuards();
        $this->actingAs(User::find($su->id))->postJson('/ogrenci/sure/ping');
        $this->assertSame(60, $s->fresh()->total_seconds); // tavan
    }

    public function test_pwa_assets_exist(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertJson(file_get_contents(public_path('manifest.webmanifest')));
        $this->get('/login')->assertSee('manifest.webmanifest', false);
    }
}
