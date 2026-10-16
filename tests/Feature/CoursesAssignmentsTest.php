<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CoursesAssignmentsTest extends TestCase
{
    use RefreshDatabase;

    private function setUpWorld(): array
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@school.local')->first();
        $class = SchoolClass::create(['name' => '5', 'section' => 'A', 'grade_level' => 5, 'academic_year' => '2026-2027']);
        $user = User::factory()->create(['role_id' => Role::where('slug', 'student')->value('id')]);
        $student = Student::create(['user_id' => $user->id, 'student_no' => '1', 'school_class_id' => $class->id]);

        return [$admin, $class, $user, $student];
    }

    public function test_course_slides_assignment_and_student_completion_awards_xp_once(): void
    {
        [$admin, $class, $user, $student] = $this->setUpWorld();

        $this->actingAs($admin)->post('/courses', ['name' => 'Scratch', 'code' => 'SCR1', 'is_active' => 1])->assertRedirect();
        $course = Course::first();
        $this->post("/courses/{$course->id}/slides", ['type' => 'text', 'title' => 'Giriş', 'body' => 'Merhaba'])->assertRedirect();
        $this->post("/courses/{$course->id}/slides", ['type' => 'quiz', 'question' => '1+1?', 'options' => "1\n2", 'answer' => 1])->assertRedirect();
        $this->assertSame([0, 1], $course->slides()->pluck('position')->all());
        $this->post("/courses/{$course->id}/siniflar", ['classes' => [$class->id]]);

        $this->post('/assignments', ['type' => 'course', 'course_id' => $course->id, 'title' => 'Dersi bitir', 'xp_reward' => 30, 'classes' => [$class->id]])->assertRedirect('/assignments');
        $assignment = Assignment::first();

        $this->actingAs($user)->get("/ogrenci/derslerim/{$course->id}")->assertOk()->assertSee('Merhaba');
        $this->post("/ogrenci/derslerim/{$course->id}/tamamla");
        $this->post("/ogrenci/derslerim/{$course->id}/tamamla");
        $this->post("/ogrenci/odevlerim/{$assignment->id}/tamamla");
        $this->post("/ogrenci/odevlerim/{$assignment->id}/tamamla");

        $this->assertSame(50, $student->fresh()->xp_balance); // 20 + 30, tekrar yok
    }

    public function test_student_cannot_open_course_of_other_class(): void
    {
        [$admin, , $user] = $this->setUpWorld();
        $course = Course::create(['name' => 'X', 'code' => 'X', 'is_active' => true]);
        $this->actingAs($user)->get("/ogrenci/derslerim/{$course->id}")->assertNotFound();
    }

    public function test_teacher_cannot_edit_foreign_course_and_export_import_roundtrip(): void
    {
        [$admin] = $this->setUpWorld();
        $teacher = User::factory()->create(['role_id' => Role::where('slug', 'teacher')->value('id')]);
        $teacher->teacher()->create([]);
        $course = Course::create(['name' => 'Y', 'code' => 'Y', 'created_by' => $admin->id]);
        $course->slides()->create(['type' => 'text', 'title' => 'a', 'content' => ['body' => 'b'], 'position' => 0]);

        $this->actingAs($teacher)->get("/courses/{$course->id}/edit")->assertForbidden();

        $json = $this->actingAs($admin)->get("/courses/{$course->id}/indir")->assertOk()->getContent();
        $this->post('/courses/import', ['file' => UploadedFile::fake()->createWithContent('c.json', $json)])->assertRedirect();
        $this->assertSame(2, Course::count());
        $this->assertSame('Y-2', Course::latest('id')->first()->code);
        $this->assertSame(1, Course::latest('id')->first()->slides()->count());
    }
}
