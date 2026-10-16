<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\XpService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolCoreTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(DatabaseSeeder::class);

        return User::where('email', 'admin@school.local')->first();
    }

    public function test_login_and_dashboard(): void
    {
        $this->admin();
        $this->post('/login', ['email' => 'admin@school.local', 'password' => 'Admin1234!'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk()->assertSee('Panel');
    }

    public function test_admin_creates_class_and_student_and_xp_ledger(): void
    {
        $this->actingAs($this->admin());
        $this->post('/classes', ['name' => '5', 'section' => 'A', 'grade_level' => 5, 'academic_year' => '2026-2027'])->assertRedirect('/classes');
        $class = SchoolClass::first();

        $this->post('/students', ['name' => 'Ali Veli', 'email' => 'ali@school.local', 'password' => 'secret1', 'student_no' => '1001', 'school_class_id' => $class->id])
            ->assertRedirect('/students');
        $student = Student::first();

        app(XpService::class)->award($student, 50, 'test');
        app(XpService::class)->award($student, -80, 'test');
        $this->assertSame(0, $student->fresh()->xp_balance);
        $this->assertSame(2, $student->xpEntries()->count());
    }

    public function test_student_cannot_manage_students(): void
    {
        $this->seed(DatabaseSeeder::class);
        $u = User::factory()->create(['role_id' => Role::where('slug', 'student')->value('id')]);
        $this->actingAs($u)->get('/students')->assertForbidden();
    }
}
