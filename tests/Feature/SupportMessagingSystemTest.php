<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\SupportRequest;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportMessagingSystemTest extends TestCase
{
    use RefreshDatabase;

    private function world(): array
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@school.local')->first();
        $tUser = User::factory()->create(['role_id' => Role::where('slug', 'teacher')->value('id')]);
        $teacher = Teacher::create(['user_id' => $tUser->id]);
        $class = SchoolClass::create(['name' => '5', 'section' => 'A', 'grade_level' => 5, 'academic_year' => '2026-2027', 'teacher_id' => $teacher->id]);
        $sUser = User::factory()->create(['role_id' => Role::where('slug', 'student')->value('id')]);
        $student = Student::create(['user_id' => $sUser->id, 'student_no' => '1', 'school_class_id' => $class->id]);

        return [$admin, $tUser, $sUser, $student];
    }

    public function test_support_thread_internal_notes_hidden_from_teacher_and_isolated(): void
    {
        [$admin, $tUser] = $this->world();
        $other = User::factory()->create(['role_id' => Role::where('slug', 'teacher')->value('id')]);

        $this->actingAs($tUser)->post('/talepler', ['subject' => 'Giriş yapamıyorum', 'message' => 'Hata alıyorum', 'category' => 'account', 'priority' => 'high'])->assertRedirect();
        $r = SupportRequest::first();
        $this->assertSame(1, $admin->notifications()->count()); // admine bildirim

        $this->actingAs($admin)->post("/talepler/{$r->id}/yanit", ['message' => 'Şifreni sıfırladım'])->assertSessionHasNoErrors();
        $this->post("/talepler/{$r->id}/yanit", ['message' => 'GİZLİ NOT', 'internal_note' => 1]);
        $this->assertSame('answered', $r->fresh()->status);
        $this->get("/talepler/{$r->id}")->assertOk()->assertSee('GİZLİ NOT'); // yonetici dahili notu gorur
        $this->assertNotNull($r->fresh()->read_at);

        $this->actingAs($tUser)->get("/talepler/{$r->id}")->assertOk()->assertSee('Şifreni sıfırladım')->assertDontSee('GİZLİ NOT');
        $this->assertSame(1, $tUser->notifications()->count()); // dahili not bildirim uretmez
        $this->actingAs($other)->get("/talepler/{$r->id}")->assertForbidden();
        $this->actingAs($tUser)->delete("/talepler/{$r->id}")->assertForbidden();

        $this->post("/talepler/{$r->id}/kapat");
        $this->post("/talepler/{$r->id}/yanit", ['message' => 'x'])->assertStatus(422);
    }

    public function test_demo_request_is_public_throttled_and_honeypot_works(): void
    {
        [$admin] = $this->world();
        $this->get('/demo')->assertOk();
        $this->post('/demo', ['name' => 'Okul A', 'email' => 'a@okul.test', 'message' => 'Merhaba'])->assertRedirect('/demo');
        $this->assertSame('demo', SupportRequest::first()->source);
        $this->post('/demo', ['name' => 'Bot', 'email' => 'b@x.test', 'message' => 'spam', 'website' => 'http://spam'])->assertRedirect('/demo');
        $this->assertSame(1, SupportRequest::count()); // bal kabi alan dolu: kaydedilmedi
        $this->actingAs($admin)->get('/talepler')->assertSee('Okul A');
    }

    public function test_student_teacher_messaging_flow(): void
    {
        [$admin, $tUser, $sUser, $student] = $this->world();
        $this->actingAs($sUser)->post('/ogrenci/mesajlar', ['to' => 'teacher', 'body' => 'Ödevi anlamadım'])->assertSessionHasNoErrors();
        $this->assertSame(1, $tUser->notifications()->count());

        $this->actingAs($admin)->get('/ogrenci-mesajlari')->assertDontSee('Ödevi anlamadım'); // yoneticiye degil
        $this->actingAs($tUser)->get('/ogrenci-mesajlari')->assertSee('Ödevi anlamadım');
        $m = \App\Models\StudentMessage::first();
        $this->post("/ogrenci-mesajlari/{$m->id}/cevapla", ['reply' => 'Yarın anlatırım'])->assertSessionHasNoErrors();

        $this->actingAs($sUser)->get('/ogrenci/mesajlar')->assertSee('Yarın anlatırım');
        $this->assertNotNull($m->fresh()->student_read_at);

        $this->actingAs($tUser)->delete("/ogrenci-mesajlari/{$m->id}");
        $this->get('/ogrenci-mesajlari')->assertDontSee('Ödevi anlamadım');
        $this->actingAs($sUser)->post('/ogrenci/mesajlar', ['to' => 'teacher', 'body' => str_repeat('a', 501)])->assertSessionHasErrors('body');
    }

    public function test_announcement_audience_and_future_publish(): void
    {
        [$admin, $tUser, $sUser] = $this->world();
        $this->actingAs($admin)->post('/announcements', ['title' => 'Sadece öğrenci', 'content' => 'x', 'audience' => 'students', 'notify' => 1])->assertSessionHasNoErrors();
        $this->post('/announcements', ['title' => 'Gelecek', 'content' => 'y', 'audience' => 'all', 'published_at' => now()->addDay()->format('Y-m-d\TH:i')]);

        $this->assertSame(1, $sUser->notifications()->count());
        $this->assertSame(0, $tUser->notifications()->count());
        $this->actingAs($sUser)->get('/duyurular')->assertSee('Sadece öğrenci')->assertDontSee('Gelecek');
        $this->actingAs($tUser)->get('/duyurular')->assertDontSee('Sadece öğrenci');
        $this->actingAs($tUser)->get('/duyuru-yonetimi')->assertForbidden();
        $this->actingAs($sUser)->get('/dashboard')->assertSee('Sadece öğrenci');
    }

    public function test_maintenance_mode_blocks_everyone_but_admin(): void
    {
        [$admin, , $sUser] = $this->world();
        $this->actingAs($admin)->post('/sistem/bakim', ['message' => 'Güncelleme yapılıyor'])->assertSessionHas('ok');
        $this->assertSame('1', Setting::get('maintenance'));

        $this->actingAs($sUser)->get('/dashboard')->assertStatus(503)->assertSee('Güncelleme yapılıyor');
        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->app['auth']->forgetGuards();
        auth()->logout();
        $this->get('/login')->assertOk();            // giris acik
        $this->get('/gizlilik')->assertOk();         // yasal sayfa acik

        $this->actingAs($admin)->post('/sistem/bakim');
        $this->assertSame('0', Setting::get('maintenance'));
        $this->actingAs($sUser)->get('/dashboard')->assertOk();
    }

    public function test_reset_student_data_requires_confirmation_and_password(): void
    {
        [$admin, , , $student] = $this->world();
        $admin->update(['password' => 'Admin1234!']);
        $student->update(['xp_total' => 50, 'xp_balance' => 50]);

        $this->actingAs($admin)->post('/sistem/ogrenci-sifirla', ['confirm' => 'evet', 'password' => 'Admin1234!'])->assertSessionHasErrors('confirm');
        $this->post('/sistem/ogrenci-sifirla', ['confirm' => 'SIFIRLA', 'password' => 'yanlis'])->assertSessionHasErrors('password');
        $this->assertSame(50, $student->fresh()->xp_total);

        $this->post('/sistem/ogrenci-sifirla', ['confirm' => 'SIFIRLA', 'password' => 'Admin1234!'])->assertSessionHas('ok');
        $this->assertSame(0, $student->fresh()->xp_total);
        $this->assertSame(1, User::where('email', 'admin@school.local')->count()); // kullanicilar kalir
    }

    public function test_legal_pages_and_kvkk_redirect(): void
    {
        $this->get('/gizlilik')->assertOk()->assertSee('KVKK');
        $this->get('/cerez')->assertOk();
        $this->get('/kosullar')->assertOk();
        $this->get('/kvkk')->assertRedirect('/gizlilik');
    }
}
