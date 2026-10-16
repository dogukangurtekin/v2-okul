<?php

namespace App\Http\Controllers;

use App\Models\LiveSession;
use App\Models\Quiz;
use App\Models\SchoolClass;
use App\Services\LiveSessionService;
use Illuminate\Http\Request;

/** Ogretmen/yonetici tarafi: oturum olustur, baslat, ilerlet, bitir, rapor. */
class LiveSessionController extends Controller
{
    public function __construct(private LiveSessionService $live) {}

    private function owned(LiveSession $s): void
    {
        abort_unless(auth()->user()->hasRole('admin') || $s->host_user_id === auth()->id(), 403);
    }

    public function index()
    {
        $sessions = LiveSession::with(['quiz', 'schoolClass'])->withCount('participants')
            ->when(! auth()->user()->hasRole('admin'), fn ($q) => $q->where('host_user_id', auth()->id()))
            ->latest()->paginate(15);

        return view('live.index', [
            'sessions' => $sessions,
            'quizzes' => Quiz::when(! auth()->user()->hasRole('admin'), fn ($q) => $q->where('teacher_user_id', auth()->id()))->get(),
            'classes' => SchoolClass::orderBy('grade_level')->get(),
            'games' => config('games'),
            'texts' => collect(config('game_levels.keyboard-race'))->pluck('text'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:quiz,competition,typing'],
            'school_class_id' => ['nullable', 'exists:school_classes,id'],
            'quiz_id' => ['required_if:type,quiz', 'nullable', 'exists:quizzes,id'],
            'game_slug' => ['required_if:type,competition', 'nullable', 'in:'.implode(',', array_keys(config('games')))],
            'level_from' => ['nullable', 'integer', 'min:1'],
            'level_to' => ['nullable', 'integer', 'gte:level_from'],
            'duration' => ['nullable', 'integer', 'between:30,3600'],
            'text' => ['required_if:type,typing', 'nullable', 'string', 'max:600'],
        ]);

        $config = match ($data['type']) {
            'competition' => [
                'game_slug' => $data['game_slug'], 'game_name' => config('games.'.$data['game_slug']),
                'level_from' => $data['level_from'] ?? 1, 'level_to' => $data['level_to'] ?? 3, 'duration' => $data['duration'] ?? 300,
            ],
            'typing' => ['text' => trim($data['text']), 'duration' => $data['duration'] ?? 120],
            default => [],
        };
        if ($data['type'] === 'quiz') {
            $quiz = Quiz::withCount('questions')->findOrFail($data['quiz_id']);
            abort_unless(auth()->user()->hasRole('admin') || $quiz->teacher_user_id === auth()->id(), 403);
            abort_if($quiz->questions_count === 0, 422, 'Quiz boş.');
        }

        $session = $this->live->create($request->user(), [
            'type' => $data['type'], 'school_class_id' => $data['school_class_id'] ?? null,
            'quiz_id' => $data['type'] === 'quiz' ? $data['quiz_id'] : null, 'config' => $config,
        ]);

        return redirect()->route('live.show', $session);
    }

    public function show(LiveSession $session)
    {
        $this->owned($session);

        return view('live.host', ['session' => $session->load('quiz', 'schoolClass')]);
    }

    public function status(LiveSession $session)
    {
        $this->owned($session);

        return response()->json($this->live->snapshot($session, false));
    }

    public function start(LiveSession $session)
    {
        $this->owned($session);
        abort_if($session->participants()->count() === 0, 422, 'Katılımcı yok.');
        $this->live->start($session);

        return back();
    }

    public function next(LiveSession $session)
    {
        $this->owned($session);
        $this->live->next($session);

        return back();
    }

    public function lock(LiveSession $session)
    {
        $this->owned($session);
        $session->update(['is_locked' => ! $session->is_locked]);

        return back();
    }

    public function finish(LiveSession $session)
    {
        $this->owned($session);
        $this->live->finish($session);

        return redirect()->route('live.report', $session);
    }

    public function report(LiveSession $session)
    {
        $this->owned($session);
        $session->load('quiz.questions', 'schoolClass');

        return view('live.report', ['session' => $session, 'ranked' => $this->live->ranked($session)]);
    }

    public function destroy(LiveSession $session)
    {
        $this->owned($session);
        $session->delete();

        return redirect()->route('live.index')->with('ok', 'Oturum silindi.');
    }
}
