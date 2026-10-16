<?php

namespace Tests\Feature;

use App\Models\Avatar;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\XpService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamificationTest extends TestCase
{
    use RefreshDatabase;

    private function student(): array
    {
        $this->seed(DatabaseSeeder::class);
        $class = SchoolClass::create(['name' => '5', 'section' => 'A', 'grade_level' => 5, 'academic_year' => '2026-2027']);
        $user = User::factory()->create(['role_id' => Role::where('slug', 'student')->value('id')]);

        return [$user, Student::create(['user_id' => $user->id, 'student_no' => '1', 'school_class_id' => $class->id])];
    }

    public function test_badges_are_awarded_by_total_xp_and_purchase_keeps_total(): void
    {
        [$user, $student] = $this->student();
        app(XpService::class)->award($student, 120, 't');
        $this->assertSame(2, $student->badges()->count()); // 10 ve 100 esikleri

        $kedi = Avatar::where('name', 'Kedi')->first();
        $this->actingAs($user)->post("/ogrenci/avatarlarim/{$kedi->id}/satinal")->assertSessionHasNoErrors();
        $student->refresh();
        $this->assertSame(70, $student->xp_balance);
        $this->assertSame(120, $student->xp_total);

        $this->post("/ogrenci/avatarlarim/{$kedi->id}/satinal")->assertSessionHasErrors('avatar'); // tekrar alinamaz
        $this->post("/ogrenci/avatarlarim/{$kedi->id}/sec");
        $this->assertSame($kedi->id, $student->fresh()->current_avatar_id);
    }

    public function test_locked_or_unaffordable_avatar_is_rejected_and_board_validates(): void
    {
        [$user, $student] = $this->student();
        $king = Avatar::where('name', 'Altın Kral')->first();
        $this->actingAs($user)->post("/ogrenci/avatarlarim/{$king->id}/satinal")->assertSessionHasErrors('avatar');

        $this->post('/ogrenci/sinif-panosu/paylas', ['message_key' => 'serbest-metin'])->assertSessionHasErrors('message_key');
        $this->post('/ogrenci/sinif-panosu/paylas', ['message_key' => 'great'])->assertSessionHasNoErrors();
        $this->get('/ogrenci/sinif-panosu')->assertOk()->assertSee('Harika bir gün');
        $this->get('/dashboard')->assertOk();
        $this->get('/ogrenci/siralama')->assertOk();
    }

    public function test_teacher_xp_grant(): void
    {
        [, $student] = $this->student();
        $admin = User::where('email', 'admin@school.local')->first();
        $this->actingAs($admin)->post('/xp-hediyesi', ['student_ids' => [$student->id], 'amount' => 25, 'reason' => 'Aferin'])->assertSessionHasNoErrors();
        $this->assertSame(25, $student->fresh()->xp_total);
    }
}
