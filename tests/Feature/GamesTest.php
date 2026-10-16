<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamesTest extends TestCase
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

        return [$admin, $teacher, $class, $sUser, $student];
    }

    public function test_locked_game_is_forbidden_until_admin_opens_it_for_teacher(): void
    {
        [$admin, $teacher, , $sUser] = $this->world();
        $this->actingAs($sUser)->get('/oyun/block-grid-runner')->assertForbidden();

        $this->actingAs($admin)->post('/etkinlikler/ogretmene-ata', ['teacher_id' => $teacher->id, 'games' => ['block-grid-runner', 'keyboard-race']])->assertSessionHasNoErrors();
        $this->actingAs($sUser)->get('/oyun/block-grid-runner')->assertOk()->assertSee('Çalıştır');
        $this->get('/oyun/keyboard-race')->assertOk();
        $this->get('/oyun/flowchart-runner')->assertForbidden();
        $this->get('/etkinlikler')->assertOk()->assertSee('Blok Kodlama');
    }

    public function test_level_flow_xp_once_and_sequential(): void
    {
        [$admin, $teacher, , $sUser, $student] = $this->world();
        $this->actingAs($admin)->post('/etkinlikler/ogretmene-ata', ['teacher_id' => $teacher->id, 'games' => ['block-grid-runner']]);
        $this->actingAs($sUser);

        $this->postJson('/oyun/block-grid-runner/sonuc', ['level' => 2, 'seconds' => 5])->assertStatus(422); // sira atlanamaz
        $this->postJson('/oyun/block-grid-runner/sonuc', ['level' => 1, 'seconds' => 5, 'score' => 10])->assertOk()->assertJson(['xp' => 10, 'repeat' => false]);
        $this->postJson('/oyun/block-grid-runner/sonuc', ['level' => 1, 'seconds' => 3])->assertOk()->assertJson(['xp' => 0, 'repeat' => true]);
        $this->postJson('/oyun/block-grid-runner/sonuc', ['level' => 2, 'seconds' => 9])->assertOk();
        $this->postJson('/oyun/block-grid-runner/sonuc', ['level' => 99, 'seconds' => 9])->assertStatus(422);
        $this->assertSame(20, $student->fresh()->xp_total);
    }

    public function test_game_assignment_completes_when_levels_done_and_opens_game(): void
    {
        [$admin, , $class, $sUser, $student] = $this->world();
        $a = Assignment::create(['type' => 'game', 'game_slug' => 'keyboard-race', 'title' => 'Yaz', 'level_from' => 1, 'level_to' => 2, 'xp_reward' => 30, 'created_by' => $admin->id]);
        $a->classes()->sync([$class->id]);

        $this->actingAs($sUser);
        $this->postJson('/oyun/keyboard-race/sonuc', ['level' => 1, 'seconds' => 5])->assertOk();
        $this->assertNull($a->submissions()->first()?->completed_at);
        $this->postJson('/oyun/keyboard-race/sonuc', ['level' => 2, 'seconds' => 5])->assertOk();
        $this->assertNotNull($a->submissions()->first()->completed_at);
        $this->assertSame(8 + 8 + 30, $student->fresh()->xp_total);
    }

    public function test_flamestone_and_block_builder_open_and_designs_are_per_user(): void
    {
        [$admin] = $this->world();
        $this->actingAs($admin)->get('/oyun/flamestone-game')->assertOk()->assertSee('runner-assets/flamestone-game/index.html', false);
        $this->actingAs($admin)->get('/oyun/block-builder-studio')->assertOk()->assertSee('block-builder-studio', false);
        $this->actingAs($admin)->get('/block-builder-studio')->assertOk()->assertSee('block-builder-page.js', false);
        $id = $this->actingAs($admin)->postJson('/block-builder-studio/designs', ['name' => 'Kule', 'blocks' => [['x' => 0, 'y' => 0, 'z' => 0, 'color' => '#ff0000']]])->assertOk()->json('design.id');
        $this->actingAs($admin)->getJson("/block-builder-studio/designs/$id")->assertOk()->assertJsonPath('design.name', 'Kule');
        $other = \App\Models\User::factory()->create();
        $this->actingAs($other)->getJson("/block-builder-studio/designs/$id")->assertNotFound();
    }

    public function test_bundle_game_serves_iframe_and_grant_for_admin(): void
    {
        [$admin] = $this->world();
        $this->actingAs($admin)->get('/oyun/flowchart-runner')->assertOk()->assertSee('runner-assets/flowchart-runner/index.html', false);
        $this->actingAs($admin)->getJson('/runner-grant/flowchart-runner')->assertOk()->assertJson(['ok' => true, 'role' => 'staff']);
        $this->assertFileExists(public_path('runner-assets/connect-the-dots-runner/levels.json'));
    }
}
