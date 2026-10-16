<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Support\AgeBand;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgeBandTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade_to_band_mapping(): void
    {
        foreach ([[1, 'kid'], [4, 'kid'], [5, 'mid'], [8, 'mid'], [9, 'teen'], [12, 'teen'], [null, 'mid']] as [$g, $b]) {
            $this->assertSame($b, AgeBand::forGrade($g), "sinif $g");
        }
    }

    private function studentInGrade(int $grade): User
    {
        $class = SchoolClass::create(['name' => (string) $grade, 'section' => 'A', 'grade_level' => $grade, 'academic_year' => '2026-2027']);
        $u = User::factory()->create(['role_id' => Role::where('slug', 'student')->value('id')]);
        Student::create(['user_id' => $u->id, 'student_no' => (string) $grade, 'school_class_id' => $class->id]);

        return $u;
    }

    public function test_each_grade_gets_its_own_shell_and_dashboard_and_all_student_pages_render(): void
    {
        $this->seed(DatabaseSeeder::class);
        foreach ([[2, 'kid', 's-hero'], [6, 'mid', 'm-hero'], [10, 'teen', 't-read']] as [$grade, $band, $marker]) {
            $u = $this->studentInGrade($grade);
            $this->actingAs($u)->get('/dashboard')->assertOk()->assertSee("band-$band", false)->assertSee($marker, false);
            foreach (['/ogrenci/derslerim', '/ogrenci/odevlerim', '/ogrenci/avatarlarim', '/ogrenci/rozetlerim', '/ogrenci/siralama', '/ogrenci/arkadaslarim', '/ogrenci/sinif-panosu', '/ogrenci/canli', '/ogrenci/karnem', '/ogrenci/devamsizlik', '/ogrenci/notlarim', '/ogrenci/mesajlar', '/duyurular', '/bildirimlerim', '/etkinlikler'] as $url) {
                $this->actingAs($u)->get($url)->assertOk();
            }
        }
    }

    public function test_preview_query_switches_band_and_auto_resets(): void
    {
        $this->seed(DatabaseSeeder::class);
        $u = $this->studentInGrade(6);
        $this->actingAs($u)->get('/dashboard?band=teen')->assertOk()->assertSee('band-teen', false);
        $this->actingAs($u)->get('/dashboard')->assertSee('band-teen', false);
        $this->actingAs($u)->get('/dashboard?band=auto')->assertSee('band-mid', false);
        $this->actingAs($u)->get('/dashboard?band=hacker')->assertOk()->assertSee('band-mid', false);
    }
}
