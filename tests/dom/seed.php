<?php

/*
 * Test veritabanını SIFIRDAN kurar ve tohumlar. KORUMA: yalnızca run.mjs'in verdiği, tests/dom/.cache altındaki
 * sqlite dosyasında çalışır; aksi halde (örn. elle çalıştırıp .env'deki MySQL'e denk gelirse) hiçbir şey yapmadan çıkar.
 */
$root = dirname(__DIR__, 2);
$cache = realpath(__DIR__.'/.cache');
$db = getenv('DB_DATABASE') ?: '';
if (getenv('DOM_TESTING') !== '1' || getenv('DB_CONNECTION') !== 'sqlite' || ! $cache || ! str_starts_with(realpath(dirname($db)) ?: '', $cache)) {
    fwrite(STDERR, "seed.php yalnızca `npm run test:dom` tarafından (DOM_TESTING=1, sqlite, tests/dom/.cache) çalıştırılır.\n");
    exit(1);
}

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);

use App\Models\{Role, SchoolClass, Student, Teacher, User};

$teacherUser = User::create(['role_id' => Role::where('slug', 'teacher')->value('id'), 'name' => 'Öğretmen', 'email' => 't@x.test', 'password' => 'password1']);
$teacher = Teacher::create(['user_id' => $teacherUser->id]);
$class = SchoolClass::create(['name' => '5', 'section' => 'A', 'grade_level' => 5, 'academic_year' => '2026-2027', 'teacher_id' => $teacher->id]);
$studentUser = User::create(['role_id' => Role::where('slug', 'student')->value('id'), 'name' => 'Ali Öğrenci', 'email' => 'ali@x.test', 'password' => 'password1']);
Student::create(['user_id' => $studentUser->id, 'student_no' => '1', 'school_class_id' => $class->id]);

foreach (['silent-teacher-runner', 'lightbot-runner', 'web-lab-runner', 'bee-garden-runner', 'python-editor'] as $slug) {
    Illuminate\Support\Facades\DB::table('teacher_games')->insert(['teacher_id' => $teacher->id, 'game_slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
}

// Silent Teacher beklenen çıktıları (cevap anahtarı yalnızca sunucuda; testler buradan okur)
file_put_contents(__DIR__.'/.cache/answers.json', json_encode(['st' => array_column(config('game_levels.silent-teacher-runner'), 'output')], JSON_UNESCAPED_UNICODE));
echo "seeded\n";
