<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\GradeService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceGradesTest extends TestCase
{
    use RefreshDatabase;

    private function world(): array
    {
        $this->seed(DatabaseSeeder::class);
        $tUser = User::factory()->create(['role_id' => Role::where('slug', 'teacher')->value('id')]);
        $teacher = Teacher::create(['user_id' => $tUser->id]);
        $mine = SchoolClass::create(['name' => '5', 'section' => 'A', 'grade_level' => 5, 'academic_year' => '2026-2027', 'teacher_id' => $teacher->id]);
        $other = SchoolClass::create(['name' => '6', 'section' => 'B', 'grade_level' => 6, 'academic_year' => '2026-2027']);
        $mk = function ($class, $no) {
            $u = User::factory()->create(['role_id' => Role::where('slug', 'student')->value('id')]);

            return [$u, Student::create(['user_id' => $u->id, 'student_no' => $no, 'school_class_id' => $class->id])];
        };

        return [$tUser, $teacher, $mine, $other, $mk($mine, '1'), $mk($mine, '2'), $mk($other, '9')];
    }

    // ---------- Yoklama ----------

    public function test_attendance_sheet_upserts_without_duplicates_and_ignores_foreign_students(): void
    {
        [$tu, , $class, , [, $s1], [, $s2], [, $foreign]] = $this->world();
        $date = today()->toDateString();
        $payload = ['school_class_id' => $class->id, 'date' => $date, 'rows' => [
            $s1->id => ['status' => 'present'], $s2->id => ['status' => 'absent', 'note' => 'hasta'], $foreign->id => ['status' => 'absent'],
        ]];

        $this->actingAs($tu)->post('/yoklama', $payload)->assertSessionHasNoErrors();
        $this->assertSame(2, Attendance::count());                       // yabanci sinif ogrencisi yazilmadi
        $this->assertNull(Attendance::where('student_id', $foreign->id)->first());

        $payload['rows'][$s2->id] = ['status' => 'excused', 'note' => 'rapor'];
        $this->post('/yoklama', $payload);
        $this->assertSame(2, Attendance::count());                       // tekrar kayit cogaltmaz (v1'deki NULL course_id acigi yok)
        $this->assertSame('excused', Attendance::where('student_id', $s2->id)->value('status'));

        $this->get("/yoklama?class={$class->id}&date={$date}")->assertOk()->assertSee('rapor');
    }

    public function test_attendance_validation_and_scope(): void
    {
        [$tu, , $class, $otherClass, [, $s1]] = $this->world();
        $base = ['school_class_id' => $class->id, 'rows' => [$s1->id => ['status' => 'present']]];

        $this->actingAs($tu)->post('/yoklama', $base + ['date' => today()->addDay()->toDateString()])->assertSessionHasErrors('date');       // gelecek
        $this->post('/yoklama', $base + ['date' => today()->subYears(2)->toDateString()])->assertSessionHasErrors('date');                  // cok eski
        $this->post('/yoklama', ['school_class_id' => $class->id, 'date' => today()->toDateString(), 'rows' => [$s1->id => ['status' => 'uyuyor']]])->assertSessionHasErrors('rows.'.$s1->id.'.status');
        $this->post('/yoklama', ['school_class_id' => $otherClass->id, 'date' => today()->toDateString(), 'rows' => [1 => ['status' => 'present']]])->assertNotFound();
        $this->get("/yoklama?class={$otherClass->id}")->assertNotFound();
    }

    public function test_attendance_course_specific_and_daily_are_independent_and_summary_rate(): void
    {
        [$tu, , $class, , [, $s1]] = $this->world();
        $course = \App\Models\Course::create(['name' => 'Scratch', 'code' => 'S1']);
        $d = today()->toDateString();
        $this->actingAs($tu);
        $this->post('/yoklama', ['school_class_id' => $class->id, 'date' => $d, 'rows' => [$s1->id => ['status' => 'present']]]);
        $this->post('/yoklama', ['school_class_id' => $class->id, 'date' => $d, 'course_id' => $course->id, 'rows' => [$s1->id => ['status' => 'absent']]]);
        $this->assertSame(2, Attendance::where('student_id', $s1->id)->count());

        // 1 geldi + 1 gelmedi => %50; izinli paydadan duser
        Attendance::create(['student_id' => $s1->id, 'date' => today()->subDay(), 'course_key' => 0, 'status' => 'excused']);
        Attendance::create(['student_id' => $s1->id, 'date' => today()->subDays(2), 'course_key' => 0, 'status' => 'late']);
        $sum = app(\App\Services\AttendanceService::class)->summary(collect([$s1->id]))[$s1->id];
        $this->assertSame(['present' => 1, 'late' => 1, 'excused' => 1, 'absent' => 1, 'total' => 4, 'rate' => 67], $sum); // (1+1)/(4-1)

        $csv = $this->get("/yoklama/rapor/indir?class={$class->id}&from=".today()->subWeek()->toDateString())->assertOk()->streamedContent();
        $this->assertStringContainsString('Devam %', $csv);
        $this->get("/yoklama/rapor?class={$class->id}")->assertOk()->assertSee('67');
    }

    public function test_student_sees_own_attendance_and_reminder_goes_only_to_teachers_missing_today(): void
    {
        [$tu, $teacher, $class, , [$su, $s1], [, $s2]] = $this->world();
        $this->actingAs($su)->get('/ogrenci/devamsizlik')->assertOk();

        $this->artisan('attendance:remind')->assertSuccessful();
        $this->assertSame(1, $tu->notifications()->where('title', 'Yoklama hatırlatması')->count());
        $this->artisan('attendance:remind');                                   // ayni gun ikinci kez gonderilmez
        $this->assertSame(1, $tu->notifications()->where('title', 'Yoklama hatırlatması')->count());

        // Yoklama girilmis ogretmene hatirlatma gitmez.
        \App\Models\AppNotification::query()->delete();
        Attendance::create(['student_id' => $s1->id, 'date' => today(), 'course_key' => 0, 'status' => 'present']);
        $this->artisan('attendance:remind');
        $this->assertSame(0, $tu->notifications()->count());
    }

    // ---------- Notlar ----------

    public function test_grade_entry_decimal_comma_blank_skip_invalid_and_scope(): void
    {
        [$tu, , $class, , [, $s1], [, $s2], [, $foreign]] = $this->world();
        $res = $this->actingAs($tu)->post('/notlar', [
            'school_class_id' => $class->id, 'exam_type' => 'midterm', 'title' => '1. Yazılı', 'exam_date' => today()->toDateString(), 'term' => '2026-2027 1. Dönem',
            'scores' => [$s1->id => '85,5', $s2->id => '', $foreign->id => '100'],
        ]);
        $res->assertSessionHasNoErrors();
        $this->assertSame(1, Grade::count());                      // bos atlandi, yabanci sinif atlandi
        $this->assertEquals(85.5, Grade::first()->score);

        $this->post('/notlar', ['school_class_id' => $class->id, 'exam_type' => 'quiz', 'exam_date' => today()->toDateString(), 'term' => 'T', 'scores' => [$s1->id => '120', $s2->id => 'abc']])
            ->assertSessionHasErrors('scores');
        $this->assertSame(1, Grade::count());
        $this->post('/notlar', ['school_class_id' => $class->id, 'exam_type' => 'oral', 'exam_date' => today()->toDateString(), 'term' => 'T', 'scores' => [$s1->id => '50']])->assertSessionHasErrors('exam_type');
    }

    public function test_weighted_average_and_current_term(): void
    {
        [, , , , [, $s1]] = $this->world();
        Grade::create(['student_id' => $s1->id, 'exam_type' => 'quiz', 'score' => 100, 'exam_date' => today(), 'term' => 'T']);     // agirlik 1
        Grade::create(['student_id' => $s1->id, 'exam_type' => 'final', 'score' => 60, 'exam_date' => today(), 'term' => 'T']);     // agirlik 3
        $svc = app(GradeService::class);
        $this->assertSame(70.0, $svc->averages(collect([$s1->id]), 'T')[$s1->id]);                       // (100*1+60*3)/4
        $this->assertNull($svc->averages(collect([$s1->id]), 'baska')[$s1->id]);

        $this->assertSame('2026-2027 1. Dönem', GradeService::currentTerm(\Carbon\Carbon::create(2026, 10, 15)));
        $this->assertSame('2026-2027 1. Dönem', GradeService::currentTerm(\Carbon\Carbon::create(2027, 1, 20)));
        $this->assertSame('2026-2027 2. Dönem', GradeService::currentTerm(\Carbon\Carbon::create(2027, 3, 1)));
        $this->assertSame('2026-2027 2. Dönem', GradeService::currentTerm(\Carbon\Carbon::create(2027, 6, 30)));
    }

    public function test_grade_edit_delete_scope_and_student_view(): void
    {
        [$tu, , $class, , [$su, $s1], , [, $foreign]] = $this->world();
        $g = Grade::create(['student_id' => $s1->id, 'exam_type' => 'quiz', 'score' => 40, 'exam_date' => today(), 'term' => '2026-2027 1. Dönem', 'title' => 'Mini']);
        $fg = Grade::create(['student_id' => $foreign->id, 'exam_type' => 'quiz', 'score' => 40, 'exam_date' => today(), 'term' => 'T']);

        $this->actingAs($tu)->put("/notlar/{$g->id}", ['exam_type' => 'quiz', 'score' => 75, 'exam_date' => today()->toDateString(), 'term' => '2026-2027 1. Dönem'])->assertSessionHasNoErrors();
        $this->assertEquals(75, $g->fresh()->score);
        $this->put("/notlar/{$fg->id}", ['exam_type' => 'quiz', 'score' => 1, 'exam_date' => today()->toDateString(), 'term' => 'T'])->assertForbidden();
        $this->delete("/notlar/{$fg->id}")->assertForbidden();
        $this->get("/notlar/{$fg->id}/duzenle")->assertForbidden();
        $this->get('/notlar?class='.$class->id.'&term=2026-2027 1. Dönem')->assertOk()->assertSee('Mini');

        $this->actingAs($su)->get('/ogrenci/notlarim')->assertOk()->assertSee('75');
        $this->actingAs($su)->get('/notlar')->assertForbidden();                       // ogrenci personel ekranina giremez

        $this->actingAs($tu)->delete("/notlar/{$g->id}");
        $this->assertNull(Grade::find($g->id));
    }

    public function test_grades_award_xp_only_when_flag_enabled(): void
    {
        [$tu, , $class, , [, $s1]] = $this->world();
        $payload = ['school_class_id' => $class->id, 'exam_type' => 'quiz', 'exam_date' => today()->toDateString(), 'term' => 'T', 'scores' => [$s1->id => '80']];

        $this->actingAs($tu)->post('/notlar', $payload);
        $this->assertSame(0, $s1->fresh()->xp_total);                                  // varsayilan: not != XP

        config(['school.grade_xp' => true]);
        $this->post('/notlar', $payload);
        $this->assertSame(80, $s1->fresh()->xp_total);
        $g = Grade::latest('id')->first();
        $this->put("/notlar/{$g->id}", ['exam_type' => 'quiz', 'score' => 90, 'exam_date' => today()->toDateString(), 'term' => 'T']);
        $this->assertSame(90, $s1->fresh()->xp_total);                                 // yalniz fark (10) eklendi
    }

    public function test_report_card_and_analytics_include_attendance_and_grades(): void
    {
        [$tu, , $class, , [$su, $s1]] = $this->world();
        Attendance::create(['student_id' => $s1->id, 'date' => today(), 'course_key' => 0, 'status' => 'present']);
        Attendance::create(['student_id' => $s1->id, 'date' => today()->subDay(), 'course_key' => 0, 'status' => 'absent']);
        Grade::create(['student_id' => $s1->id, 'exam_type' => 'final', 'score' => 88, 'exam_date' => today(), 'term' => 'T']);

        $stats = app(AnalyticsService::class)->forStudents(collect([$s1]))[$s1->id];
        $this->assertSame(50, $stats['attendance_rate']);
        $this->assertSame(1, $stats['absent_days']);
        $this->assertSame(88.0, $stats['grade_avg']);

        $this->actingAs($tu)->get("/rapor/{$s1->id}")->assertOk()->assertSee('Not ortalaması')->assertSee('88');
        $this->get('/analitik')->assertOk()->assertSee('Not ort.');
        $this->assertStringContainsString('Devam %', $this->get('/analitik/indir')->streamedContent());
    }
}
