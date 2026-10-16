<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    private function owned(Quiz $quiz): void
    {
        abort_unless(auth()->user()->hasRole('admin') || $quiz->teacher_user_id === auth()->id(), 403);
    }

    public function index()
    {
        $quizzes = Quiz::withCount('questions')
            ->when(! auth()->user()->hasRole('admin'), fn ($q) => $q->where('teacher_user_id', auth()->id()))
            ->latest()->paginate(20);

        return view('quizzes.index', compact('quizzes'));
    }

    public function create()
    {
        return view('quizzes.form', ['quiz' => new Quiz, 'existing' => []]);
    }

    public function edit(Quiz $quiz)
    {
        $this->owned($quiz);

        $quiz->load('questions');

        return view('quizzes.form', ['quiz' => $quiz, 'existing' => $quiz->questions->map(fn ($q) => [
            'text' => $q->text, 'options' => implode("\n", $q->options), 'correct' => $q->correct_index,
            'duration' => $q->duration_sec, 'xp' => $q->xp, 'double' => $q->double_xp,
        ])->values()->all()]);
    }

    private function save(Request $request, Quiz $quiz): Quiz
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'questions' => ['required', 'array', 'min:1', 'max:50'],
            'questions.*.text' => ['required', 'string'],
            'questions.*.options' => ['required', 'string'],   // satir basina bir secenek
            'questions.*.correct' => ['required', 'integer', 'min:0'],
            'questions.*.duration' => ['required', 'integer', 'between:5,120'],
            'questions.*.xp' => ['required', 'integer', 'between:1,100'],
            'questions.*.double' => ['nullable', 'boolean'],
        ]);

        return DB::transaction(function () use ($data, $quiz, $request) {
            $quiz->fill(['title' => $data['title'], 'teacher_user_id' => $quiz->teacher_user_id ?: $request->user()->id])->save();
            $quiz->questions()->delete();
            foreach (array_values($data['questions']) as $i => $q) {
                $options = array_values(array_filter(array_map('trim', preg_split('/\R/', $q['options']))));
                abort_if(count($options) < 2 || $q['correct'] >= count($options), 422, 'Her soru en az 2 seçenek içermeli ve doğru cevap geçerli olmalı.');
                $quiz->questions()->create([
                    'position' => $i, 'text' => $q['text'], 'options' => $options, 'correct_index' => $q['correct'],
                    'duration_sec' => $q['duration'], 'xp' => $q['xp'], 'double_xp' => ! empty($q['double']),
                ]);
            }

            return $quiz;
        });
    }

    public function store(Request $request)
    {
        $quiz = $this->save($request, new Quiz);

        return redirect()->route('quizzes.index')->with('ok', 'Quiz kaydedildi.');
    }

    public function update(Request $request, Quiz $quiz)
    {
        $this->owned($quiz);
        $this->save($request, $quiz);

        return redirect()->route('quizzes.index')->with('ok', 'Quiz güncellendi.');
    }

    public function destroy(Quiz $quiz)
    {
        $this->owned($quiz);
        $quiz->delete();

        return back()->with('ok', 'Quiz silindi.');
    }
}
