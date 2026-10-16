<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Announcement;
use App\Models\Course;
use App\Models\LiveSession;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\SupportRequest;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Her ekranı gerçekten açar (Blade derleme/sorgu hatalarını yakalar). Üretimde, hiçbir testin açmadığı
 * /quizzes/create sayfasında Blade derleme hatası çıkınca eklendi.
 */
class AdminPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function seedWorld(): array
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@school.local')->first();
        $tUser = User::factory()->create(['role_id' => Role::where('slug', 'teacher')->value('id')]);
        $teacher = Teacher::create(['user_id' => $tUser->id, 'branch' => 'Bilişim']);
        $class = SchoolClass::create(['name' => '5', 'section' => 'A', 'grade_level' => 5, 'academic_year' => '2026-2027', 'teacher_id' => $teacher->id]);
        $sUser = User::factory()->create(['role_id' => Role::where('slug', 'student')->value('id')]);
        $student = Student::create(['user_id' => $sUser->id, 'student_no' => '1', 'school_class_id' => $class->id]);
        $course = Course::create(['name' => 'Ders', 'code' => 'D1', 'created_by' => $admin->id, 'teacher_id' => $teacher->id]);
        $course->slides()->create(['type' => 'text', 'title' => 'a', 'content' => ['body' => 'b'], 'position' => 0]);
        $course->classes()->sync([$class->id]);
        $quiz = Quiz::create(['teacher_user_id' => $admin->id, 'title' => 'Quiz "özel" \'tırnak\'']);
        $quiz->questions()->create(['position' => 0, 'text' => "Satır 1\nSatır 2 </script>", 'options' => ['A', "B'li", 'C"li'], 'correct_index' => 1, 'duration_sec' => 20, 'xp' => 10, 'double_xp' => true]);
        $assignment = Assignment::create(['type' => 'course', 'course_id' => $course->id, 'title' => 'Ödev', 'xp_reward' => 5, 'created_by' => $admin->id]);
        $assignment->classes()->sync([$class->id]);
        $session = app(\App\Services\LiveSessionService::class)->create($admin, ['type' => 'quiz', 'quiz_id' => $quiz->id, 'config' => []]);
        $announcement = Announcement::create(['title' => 'Duyuru', 'content' => 'x', 'audience' => 'all', 'published_by' => $admin->id, 'published_at' => now()]);
        $support = SupportRequest::create(['sender_user_id' => $tUser->id, 'subject' => 'Konu', 'message' => 'm', 'category' => 'other']);

        return compact('admin', 'tUser', 'sUser', 'student', 'class', 'course', 'quiz', 'assignment', 'session', 'announcement', 'support', 'teacher');
    }

    public function test_every_staff_page_renders_for_admin(): void
    {
        $w = $this->seedWorld();
        $this->actingAs($w['admin']);
        $urls = [
            '/dashboard', '/classes', '/classes/create', "/classes/{$w['class']->id}/edit", '/students', '/students/create', "/students/{$w['student']->id}/edit",
            '/courses', '/courses/create', "/courses/{$w['course']->id}/edit", "/courses/{$w['course']->id}/onizle",
            '/assignments', '/assignments/create', "/assignments/{$w['assignment']->id}", "/assignments/{$w['assignment']->id}/edit",
            '/quizzes', '/quizzes/create', "/quizzes/{$w['quiz']->id}/edit",
            '/canli', "/canli/{$w['session']->id}", '/etkinlikler', '/kullanicilar', '/kullanicilar/create', "/kullanicilar/{$w['tUser']->id}/edit", "/kullanicilar/{$w['tUser']->id}/siniflar",
            '/toplu-yukleme', '/giris-kartlari', '/analitik', '/takip', "/takip/ogrenci/{$w['student']->id}", "/rapor/{$w['student']->id}",
            '/yoklama', "/yoklama?class={$w['class']->id}", "/yoklama/rapor?class={$w['class']->id}", '/notlar', '/notlar/giris', "/notlar/giris?class={$w['class']->id}",
            '/duyuru-yonetimi', '/announcements/create', "/announcements/{$w['announcement']->id}/edit", '/duyurular', '/sistem',
            '/talepler', "/talepler/{$w['support']->id}", '/ogrenci-mesajlari', '/bildirim-gonder', '/bildirimlerim', '/bildirimlerim',
            '/oyun/silent-teacher-runner', '/oyun/lightbot-runner', '/oyun/web-lab-runner', '/oyun/bee-garden-runner', '/oyun/python-editor', '/oyun/block-grid-runner', '/oyun/keyboard-race',
        ];
        foreach (array_unique($urls) as $u) {
            $r = $this->get($u);
            $this->assertSame(200, $r->getStatusCode(), "$u → ".$r->getStatusCode().' '.mb_substr(strip_tags((string) $r->getContent()), 0, 200));
        }
    }

    public function test_every_student_page_renders_for_student(): void
    {
        $w = $this->seedWorld();
        $this->actingAs($w['sUser']);
        foreach (['/dashboard', '/ogrenci/derslerim', "/ogrenci/derslerim/{$w['course']->id}", '/ogrenci/odevlerim', '/ogrenci/avatarlarim', '/ogrenci/rozetlerim', '/ogrenci/siralama',
            '/ogrenci/arkadaslarim', '/ogrenci/sinif-panosu', '/ogrenci/canli', '/ogrenci/karnem', '/ogrenci/devamsizlik', '/ogrenci/notlarim', '/ogrenci/mesajlar', '/duyurular', '/bildirimlerim', '/etkinlikler'] as $u) {
            $r = $this->get($u);
            $this->assertSame(200, $r->getStatusCode(), "$u → ".$r->getStatusCode().' '.mb_substr(strip_tags((string) $r->getContent()), 0, 200));
        }
    }

    public function test_quiz_form_edit_page_embeds_questions_safely(): void
    {
        $w = $this->seedWorld();
        $html = $this->actingAs($w['admin'])->get("/quizzes/{$w['quiz']->id}/edit")->assertOk()->getContent();
        $this->assertStringContainsString('const existing = [', $html);
        $this->assertStringNotContainsString('</script> ', explode('const existing', $html, 2)[1] ?? '', 'soru metnindeki </script> betiği bozmamalı');
        $this->assertStringContainsString('Sat\\u0131r 1', $html);        // @json Unicode'u \uXXXX yazar
        $this->assertStringContainsString('\\u003C\\/script\\u003E', $html);   // @json '<' ve '>' karakterlerini hex kaçırır: </script> betik bloğunu bozamaz
        $this->get('/quizzes/create')->assertOk()->assertSee('const existing = []', false);
    }
}
