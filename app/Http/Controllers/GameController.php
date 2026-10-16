<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\GameProgress;
use App\Services\GameAccessService;
use App\Services\GameProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class GameController extends Controller
{
    public function __construct(private GameAccessService $access) {}

    private function isStaff(): bool
    {
        return auth()->user()->hasRole('admin', 'teacher');
    }

    private function assertKnown(string $slug): void
    {
        abort_unless(isset(config('games')[$slug], config('runners')[$slug]), 404);
    }

    public function index()
    {
        $user = auth()->user();
        $slugs = array_keys(config('games'));

        if ($user->hasRole('student')) {
            $student = $user->student ?? abort(403);
            $slugs = $this->access->allowedSlugs($student);
            $progress = GameProgress::where('student_id', $student->id)->get()->groupBy('game_slug')->map->count();
        } elseif ($user->hasRole('teacher')) {
            $mine = DB::table('teacher_games')->where('teacher_id', $user->teacher?->id)->pluck('game_slug')->all();
            $slugs = $mine;
        }

        return view('games.index', [
            'slugs' => $slugs,
            'progress' => $progress ?? collect(),
            'teachers' => $user->hasRole('admin') ? Teacher::with('user')->get() : collect(),
            'unlocked' => $user->hasRole('admin') ? DB::table('teacher_games')->get()->groupBy('teacher_id')->map->pluck('game_slug') : collect(),
        ]);
    }

    public function play(string $slug)
    {
        $this->assertKnown($slug);
        $user = auth()->user();
        $student = $user->student;

        // Ogrenci yalniz acilmis oyunu oynar; ogretmen yalniz kendisine atananlari; yonetici hepsini onizler.
        if ($user->hasRole('student')) {
            abort_unless($student && $this->access->canPlay($student, $slug), 403, 'Bu oyun sınıfınıza açılmamış.');
        } elseif ($user->hasRole('teacher')) {
            abort_unless(DB::table('teacher_games')->where(['teacher_id' => $user->teacher?->id, 'game_slug' => $slug])->exists(), 403);
        }

        $cfg = config("runners.$slug");
        $done = $student ? GameProgress::where(['student_id' => $student->id, 'game_slug' => $slug])->pluck('level')->all() : [];
        $data = ['slug' => $slug, 'name' => config("games.$slug"), 'cfg' => $cfg, 'done' => $done, 'canRecord' => (bool) $student];

        if ($cfg['kind'] === 'route') {
            return redirect('/'); // Faz 5: canli quiz
        }
        if ($cfg['kind'] === 'native' && view()->exists("games.native.$slug")) {
            $levels = config("game_levels.$slug");
            $verifier = $this->verifier($slug);

            // Cevap anahtarli oyunlarda istemciye yalniz public alanlar gider.
            return view("games.native.$slug", $data + ['levels' => $verifier ? $verifier->publicLevels($levels) : $levels]);
        }
        if (File::exists(public_path("runner-assets/$slug/index.html"))) {
            return view('games.bundle', $data);
        }

        return view('games.soon', $data);
    }

    private function verifier(string $slug): ?\App\Games\GameVerifier
    {
        $class = config("runners.$slug.verifier");

        return $class ? app($class) : null;
    }

    /** Paket (iframe) oyunlarin yetki sorgusu: v1 /runner-grant/{slug} sozlesmesi. */
    public function grant(Request $request, string $slug)
    {
        $this->assertKnown($slug);
        $user = $request->user();
        $max = (int) config("runners.$slug.levels", 1);
        if ($user->hasRole('admin', 'teacher')) {
            return response()->json(['ok' => true, 'role' => 'staff']);
        }
        $student = $user->student ?? abort(403);
        abort_unless($this->access->canPlay($student, $slug), 403);

        return response()->json(['ok' => true, 'role' => 'student', 'from' => 1, 'to' => $max]);
    }

    public function result(Request $request, string $slug, GameProgressService $progress)
    {
        $this->assertKnown($slug);
        $student = $request->user()->student ?? abort(403);
        abort_unless($this->access->canPlay($student, $slug), 403);

        $data = $request->validate([
            'level' => ['required', 'integer', 'min:1'],
            'seconds' => ['required', 'integer', 'min:0', 'max:36000'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $score = $data['score'] ?? 0;
        $explain = null;
        if ($verifier = $this->verifier($slug)) {
            // Dogrulamali oyun: XP yalniz sunucu cozumu onaylarsa. Basarisizlik 200 + ok:false (oyun akisi hata degil).
            $v = $verifier->verify($data['level'], (array) $request->input('payload', []));
            if (! $v['ok']) {
                return response()->json(['ok' => false, 'messages' => $v['messages']]);
            }
            $score = $v['score'];
            $explain = $v['explain'] ?? null;
        }

        $res = $progress->record($student, $slug, $data['level'], $data['seconds'], $score);
        abort_if($res === null, 422, 'Seviye kabul edilmedi.');

        return response()->json($res + ['ok' => true, 'explain' => $explain, 'xp_balance' => $student->fresh()->xp_balance]);
    }

    /** Personel onizlemesi: cozumu dogrular, XP/ilerleme yazmaz. */
    public function check(Request $request, string $slug)
    {
        $this->assertKnown($slug);
        abort_unless($request->user()->hasRole('admin', 'teacher'), 403);
        $verifier = $this->verifier($slug) ?? abort(404);
        $level = $request->validate(['level' => ['required', 'integer', 'min:1']])['level'];
        $v = $verifier->verify($level, (array) $request->input('payload', []));

        return response()->json(['ok' => $v['ok'], 'messages' => $v['messages'], 'explain' => $v['ok'] ? ($v['explain'] ?? null) : null, 'xp' => 0, 'preview' => true]);
    }

    /** Yonetici: ogretmene oyun ac/kapat (v1 ogretmene-ata/toplu). */
    public function assignTeacher(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        $data = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'games' => ['nullable', 'array'],
            'games.*' => ['in:'.implode(',', array_keys(config('games')))],
        ]);

        DB::transaction(function () use ($data, $request) {
            DB::table('teacher_games')->where('teacher_id', $data['teacher_id'])->delete();
            foreach ($data['games'] ?? [] as $slug) {
                DB::table('teacher_games')->insert([
                    'teacher_id' => $data['teacher_id'], 'game_slug' => $slug,
                    'assigned_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        return back()->with('ok', 'Oyun atamaları kaydedildi.');
    }
}
