<?php

namespace Tests\Feature;

use App\Models\LoginToken;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\LoginTokenService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UserManagementImportQrTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(DatabaseSeeder::class);

        return User::where('email', 'admin@school.local')->first();
    }

    private function teacher(): array
    {
        $u = User::factory()->create(['role_id' => Role::where('slug', 'teacher')->value('id')]);

        return [$u, Teacher::create(['user_id' => $u->id])];
    }

    private function student(SchoolClass $class, string $no = '1'): array
    {
        $u = User::factory()->create(['role_id' => Role::where('slug', 'student')->value('id')]);

        return [$u, Student::create(['user_id' => $u->id, 'student_no' => $no, 'school_class_id' => $class->id])];
    }

    private function class(string $n = '5', string $s = 'A', ?int $teacherId = null): SchoolClass
    {
        return SchoolClass::create(['name' => $n, 'section' => $s, 'grade_level' => (int) $n, 'academic_year' => '2026-2027', 'teacher_id' => $teacherId]);
    }

    // ---------- Kullanici yonetimi ----------

    public function test_admin_creates_teacher_assigns_classes_and_guards_hold(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/kullanicilar', ['name' => 'Ayşe Hoca', 'email' => 'ayse@okul.test', 'role' => 'teacher', 'password' => 'sifre1234', 'branch' => 'Bilişim', 'is_active' => 1])->assertSessionHasNoErrors();
        $t = User::where('email', 'ayse@okul.test')->first();
        $this->assertNotNull($t->teacher);

        $a = $this->class('5', 'A');
        $b = $this->class('5', 'B');
        $c = $this->class('6', 'A');
        $this->post("/kullanicilar/{$t->id}/siniflar", ['classes' => [$a->id, $b->id]]);
        $this->assertSame($t->teacher->id, $a->fresh()->teacher_id);
        $this->post("/kullanicilar/{$t->id}/siniflar", ['classes' => [$b->id]]); // a serbest kalir
        $this->assertNull($a->fresh()->teacher_id);
        $this->post("/kullanicilar/{$t->id}/siniflar/kademe", ['grade_level' => 6]);
        $this->assertSame($t->teacher->id, $c->fresh()->teacher_id);

        // Kendini silemez / pasiflestiremez; son yonetici korunur.
        $this->delete("/kullanicilar/{$admin->id}")->assertStatus(422);
        $this->put("/kullanicilar/{$admin->id}", ['name' => 'x', 'email' => $admin->email, 'role' => 'admin'])->assertStatus(422); // is_active yok = pasif
        // Ogretmeni silince sinif atamasi bosalir
        $this->delete("/kullanicilar/{$t->id}");
        $this->assertNull($b->fresh()->teacher_id);
        $this->assertNull(User::find($t->id));
    }

    public function test_demoting_last_admin_and_teacher_access_are_blocked(): void
    {
        $admin = $this->admin();
        $second = User::factory()->create(['role_id' => Role::where('slug', 'admin')->value('id')]);
        $this->actingAs($admin)->put("/kullanicilar/{$second->id}", ['name' => 'B', 'email' => $second->email, 'role' => 'teacher', 'is_active' => 1])->assertSessionHasNoErrors(); // iki yonetici var: serbest
        $this->assertTrue($second->fresh()->hasRole('teacher'));

        [$tu] = $this->teacher();
        $this->actingAs($tu)->get('/kullanicilar')->assertForbidden();
        $this->post('/toplu-yukleme/student')->assertForbidden();
    }

    public function test_password_reset_shows_temp_password_and_kills_sessions(): void
    {
        $admin = $this->admin();
        [$tu] = $this->teacher();
        $res = $this->actingAs($admin)->post("/kullanicilar/{$tu->id}/sifre-sifirla");
        $msg = session('ok');
        preg_match('/: (\S+) —/', $msg, $m);
        $this->assertTrue(\Hash::check($m[1], $tu->fresh()->password));
        $this->assertNotNull($tu->fresh()->force_logout_at);
    }

    public function test_bulk_delete_selected_students(): void
    {
        $admin = $this->admin();
        $class = $this->class();
        [, $s1] = $this->student($class, '1');
        [, $s2] = $this->student($class, '2');
        $this->actingAs($admin)->post('/ogrenciler/secili-sil', ['ids' => [$s1->id]])->assertSessionHas('ok');
        $this->assertNull(Student::find($s1->id));
        $this->assertNotNull(Student::find($s2->id));
    }

    // ---------- Toplu yukleme ----------

    public function test_student_csv_import_with_turkish_excel_format_errors_and_generated_passwords(): void
    {
        $admin = $this->admin();
        $this->class('5', 'A');
        $csv = "ad_soyad;ogrenci_no;sinif;kullanici_adi;sifre;veli_adi;veli_telefon\n"
            ."Ali Yılmaz;1001;5-A;;;Ayşe;0532 111 22 33\n"
            ."Zeynep Şahin;1002;7/b;zeynep@x.test;gizli123;;\n"        // sinif yok -> olusturulur
            ."Mükerrer;1001;5-A;;;;\n"                                  // ayni no
            .";1003;5-A;;;;\n"                                          // ad yok
            ."Kötü Sınıf;1004;xyz;;;;\n";
        $file = UploadedFile::fake()->createWithContent('ogr.csv', mb_convert_encoding($csv, 'Windows-1254', 'UTF-8'));

        $res = $this->actingAs($admin)->post('/toplu-yukleme/student', ['file' => $file, 'create_classes' => 1])->assertOk();
        $res->assertSee('2')->assertSee('Satır 4')->assertSee('Satır 5')->assertSee('Satır 6');

        $ali = User::where('email', '1001@school.local')->first();
        $this->assertNotNull($ali);
        $this->assertSame('Ali Yılmaz', $ali->name);
        $this->assertSame('905321112233', app(\App\Services\ParentReportService::class)->phone($ali->student->parent_phone));
        $this->assertSame('5-A', $ali->student->schoolClass->label);
        $this->assertSame('7-B', User::where('email', 'zeynep@x.test')->first()->student->schoolClass->label);
        $this->assertTrue(\Hash::check('gizli123', User::where('email', 'zeynep@x.test')->value('password')));
        $this->assertNull(User::where('email', '1004@school.local')->first());
        $this->assertSame(1, Student::where('student_no', '1001')->count());
        // Uretilen sifre sonuc sayfasinda gorunur ve hesapla eslesir; veritabaninda duz metin yoktur.
        preg_match('#1001@school\\.local</td><td><code[^>]*>(\\S{8})</code>#', $res->getContent(), $pm);
        $this->assertNotEmpty($pm);
        $this->assertTrue(\Hash::check($pm[1], $ali->password));
    }

    public function test_xlsx_import_reads_first_sheet(): void
    {
        $admin = $this->admin();
        $this->class('5', 'A');
        $path = tempnam(sys_get_temp_dir(), 'x').'.xlsx';
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::CREATE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>');
        $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><t>ad_soyad</t></si><si><t>ogrenci_no</t></si><si><t>sinif</t></si><si><t>Can Öz</t></si><si><t>5-A</t></si></sst>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'
            .'<row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c><c r="C1" t="s"><v>2</v></c></row>'
            .'<row r="2"><c r="A2" t="s"><v>3</v></c><c r="B2"><v>2001</v></c><c r="C2" t="s"><v>4</v></c></row></sheetData></worksheet>');
        $zip->close();

        $this->actingAs($admin)->post('/toplu-yukleme/student', ['file' => new UploadedFile($path, 'ogr.xlsx', null, null, true)])->assertOk();
        $u = User::where('email', '2001@school.local')->first();
        $this->assertSame('Can Öz', $u->name);
        $this->assertSame('5-A', $u->student->schoolClass->label);
    }

    public function test_teacher_import_and_bad_files(): void
    {
        $admin = $this->admin();
        $csv = "ad_soyad,eposta,brans,telefon,sifre\nMehmet Demir,mehmet@okul.test,Bilişim,0533 444 55 66,\nHatalı,epostayok,,,\n";
        $this->actingAs($admin)->post('/toplu-yukleme/teacher', ['file' => UploadedFile::fake()->createWithContent('o.csv', $csv)])->assertOk()->assertSee('Satır 3');
        $this->assertNotNull(User::where('email', 'mehmet@okul.test')->first()->teacher);

        $this->post('/toplu-yukleme/student', ['file' => UploadedFile::fake()->createWithContent('x.pdf', 'zzz')])->assertSessionHasErrors('file');
        $this->post('/toplu-yukleme/student', ['file' => UploadedFile::fake()->createWithContent('bos.csv', "ad_soyad;ogrenci_no\n")])->assertSessionHasErrors('file');
        $this->get('/toplu-yukleme/student/sablon')->assertOk();
    }

    // ---------- Giris kartlari ve QR ----------

    public function test_login_card_is_single_use_confirm_page_does_not_consume_and_old_cards_revoked(): void
    {
        $admin = $this->admin();
        [$tu, $teacher] = $this->teacher();
        $class = $this->class('5', 'A', $teacher->id);
        [$su, $s] = $this->student($class);

        $html = $this->actingAs($tu)->post("/giris-kartlari/{$class->id}")->assertOk()->getContent();
        preg_match('#/giris-karti/([A-Za-z0-9]{40})#', $html, $m);
        $token = $m[1];
        $this->assertStringNotContainsString('password', strtolower($html));
        $this->assertSame(1, LoginToken::where('kind', 'card')->count());

        auth()->logout();
        $this->app['auth']->forgetGuards();
        $this->get("/giris-karti/{$token}")->assertOk()->assertSee($su->name);
        $this->get("/giris-karti/{$token}")->assertOk(); // onizleme tuketmez
        $this->post("/giris-karti/{$token}")->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($su);

        auth()->logout();
        $this->app['auth']->forgetGuards();
        $this->post("/giris-karti/{$token}")->assertNotFound(); // ikinci kullanim yok

        // Yeni kart basilinca eski kullanilmamis kart iptal.
        $this->actingAs($tu)->post("/giris-kartlari/{$class->id}");
        $first = LoginToken::where('kind', 'card')->first();
        $this->post("/giris-kartlari/{$class->id}");
        $this->assertSame(1, LoginToken::where('kind', 'card')->whereNull('used_at')->count());

        // Baska ogretmen baska sinifa kart basamaz.
        [$other] = $this->teacher();
        $this->actingAs($other)->post("/giris-kartlari/{$class->id}")->assertForbidden();
    }

    public function test_expired_card_and_inactive_student_cannot_login(): void
    {
        $admin = $this->admin();
        $class = $this->class();
        [$su] = $this->student($class);
        $svc = app(LoginTokenService::class);
        $token = $svc->issueCard($su, $admin);
        LoginToken::query()->update(['expires_at' => now()->subMinute()]);
        $this->post("/giris-karti/{$token}")->assertNotFound();

        $token2 = $svc->issueCard($su, $admin);
        $su->update(['is_active' => false]);
        $this->post("/giris-karti/{$token2}")->assertNotFound();
        $this->assertGuest();
    }

    public function test_qr_login_flow_requires_staff_approval_and_poll_secret(): void
    {
        $admin = $this->admin();
        [$tu, $teacher] = $this->teacher();
        $class = $this->class('5', 'A', $teacher->id);
        [$su, $s] = $this->student($class);
        [, $foreign] = $this->student($this->class('6', 'B'), '99');

        // Giris ekraninda (misafir) QR uretilir.
        $j = $this->postJson('/qr/uret')->assertOk()->json();
        $this->assertStringContainsString("/qr/onay/{$j['token']}", $j['url']);
        $this->getJson("/qr/durum/{$j['token']}?poll={$j['poll']}")->assertJson(['status' => 'pending']);
        $this->getJson("/qr/durum/{$j['token']}?poll=yanlis")->assertJson(['status' => 'expired']);
        $this->postJson("/qr/tamamla/{$j['token']}", ['poll' => $j['poll']])->assertForbidden(); // onaylanmadi

        // Onay: ogrenci kendi basina onaylayamaz, ogretmen yalniz kendi sinifindan secebilir.
        $this->actingAs($su)->get("/qr/onay/{$j['token']}")->assertForbidden();
        $this->actingAs($tu)->get("/qr/onay/{$j['token']}")->assertOk()->assertSee('Giriş isteyen cihaz');
        $this->post("/qr/onay/{$j['token']}", ['student_id' => $foreign->id])->assertNotFound();
        $this->post("/qr/onay/{$j['token']}", ['student_id' => $s->id])->assertRedirect('/dashboard');

        auth()->logout();
        $this->app['auth']->forgetGuards();
        $this->getJson("/qr/durum/{$j['token']}?poll={$j['poll']}")->assertJson(['status' => 'approved']);
        $this->postJson("/qr/tamamla/{$j['token']}", ['poll' => 'yanlis'])->assertForbidden();   // QR'i goren ama poll sirrini bilmeyen giremez
        $this->postJson("/qr/tamamla/{$j['token']}", ['poll' => $j['poll']])->assertOk()->assertJson(['redirect' => url('/dashboard')]);
        $this->assertAuthenticatedAs($su);

        auth()->logout();
        $this->app['auth']->forgetGuards();
        $this->postJson("/qr/tamamla/{$j['token']}", ['poll' => $j['poll']])->assertForbidden(); // tek kullanim
    }

    public function test_expired_qr_cannot_be_approved(): void
    {
        $this->admin();
        [$tu, $teacher] = $this->teacher();
        $class = $this->class('5', 'A', $teacher->id);
        [, $s] = $this->student($class);
        $j = $this->postJson('/qr/uret')->json();
        LoginToken::query()->update(['expires_at' => now()->subSecond()]);
        $this->actingAs($tu)->get("/qr/onay/{$j['token']}")->assertNotFound();
        $this->post("/qr/onay/{$j['token']}", ['student_id' => $s->id])->assertNotFound();
    }

    public function test_login_page_has_qr_button(): void
    {
        $this->get('/login')->assertOk()->assertSee('QR ile giriş');
    }
}
