<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Services\GradeService;
use App\Support\StaffScope;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function __construct(private GradeService $svc) {}

    private function classFor(Request $request, int $id): SchoolClass
    {
        return StaffScope::classes($request->user())->findOrFail($id);
    }

    private function ownedGrade(Request $request, Grade $grade): void
    {
        abort_unless(StaffScope::students($request->user())->whereKey($grade->student_id)->exists(), 403);
    }

    /** Sinif icin not giris cizelgesi. */
    public function sheet(Request $request)
    {
        $class = $request->filled('class') ? $this->classFor($request, $request->integer('class')) : null;

        return view('grades.sheet', [
            'classes' => StaffScope::classes($request->user())->get(), 'class' => $class,
            'students' => $class ? $class->students()->with('user')->get()->sortBy(fn ($s) => $s->user->name)->values() : collect(),
            'courses' => $class ? Course::whereHas('classes', fn ($q) => $q->where('school_classes.id', $class->id))->orderBy('name')->get() : collect(),
            'term' => $request->input('term', GradeService::currentTerm()),
        ]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'school_class_id' => ['required', 'integer'],
            'exam_type' => ['required', 'in:'.implode(',', array_keys(config('school.exam_types')))],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'title' => ['nullable', 'string', 'max:120'],
            'exam_date' => ['required', 'date', 'before_or_equal:today'],
            'term' => ['required', 'string', 'max:40'],
            'scores' => ['required', 'array', 'max:200'],
            'scores.*' => ['nullable', 'string', 'max:6'],
        ]);
        $class = $this->classFor($request, $d['school_class_id']);
        $ids = $class->students()->pluck('id');

        $saved = 0;
        $bad = [];
        foreach ($d['scores'] as $studentId => $raw) {
            $raw = str_replace(',', '.', trim((string) $raw));
            if ($raw === '' || ! $ids->contains((int) $studentId)) {
                continue; // bos = girilmedi
            }
            if (! is_numeric($raw) || $raw < 0 || $raw > 100) {
                $bad[] = $studentId;
                continue;
            }
            $this->svc->create([
                'student_id' => (int) $studentId, 'course_id' => $d['course_id'] ?? null, 'exam_type' => $d['exam_type'], 'title' => $d['title'] ?? null,
                'score' => round((float) $raw, 2), 'exam_date' => $d['exam_date'], 'term' => $d['term'], 'recorded_by' => $request->user()->id,
            ]);
            $saved++;
        }

        $back = redirect()->route('grades.index', ['class' => $class->id, 'term' => $d['term']])->with('ok', "$saved not kaydedildi.");

        return $bad ? $back->withErrors(['scores' => count($bad).' satırda geçersiz not atlandı (0–100 arası sayı girin).']) : $back;
    }

    public function index(Request $request)
    {
        $classes = StaffScope::classes($request->user())->get();
        $class = $request->filled('class') ? $this->classFor($request, $request->integer('class')) : null;
        $term = $request->input('term', GradeService::currentTerm());

        $grades = Grade::with('student.user', 'course')->whereIn('student_id', StaffScope::students($request->user())->select('id'))
            ->when($class, fn ($q) => $q->whereIn('student_id', $class->students()->select('id')))
            ->where('term', $term)
            ->when($request->filled('type'), fn ($q) => $q->where('exam_type', $request->type))
            ->latest('exam_date')->latest('id')->paginate(30)->withQueryString();

        $students = $class ? $class->students()->with('user')->get()->sortBy(fn ($s) => $s->user->name)->values() : collect();

        return view('grades.index', [
            'classes' => $classes, 'class' => $class, 'term' => $term, 'grades' => $grades, 'students' => $students,
            'avg' => $students->isNotEmpty() ? $this->svc->averages($students->pluck('id'), $term) : [],
            'terms' => Grade::select('term')->distinct()->orderByDesc('term')->pluck('term')->prepend($term)->unique()->values(),
        ]);
    }

    public function edit(Request $request, Grade $grade)
    {
        $this->ownedGrade($request, $grade);

        return view('grades.edit', ['grade' => $grade->load('student.user'), 'courses' => Course::orderBy('name')->get()]);
    }

    public function update(Request $request, Grade $grade)
    {
        $this->ownedGrade($request, $grade);
        $d = $request->validate([
            'exam_type' => ['required', 'in:'.implode(',', array_keys(config('school.exam_types')))], 'title' => ['nullable', 'string', 'max:120'],
            'score' => ['required', 'numeric', 'between:0,100'], 'exam_date' => ['required', 'date', 'before_or_equal:today'],
            'term' => ['required', 'string', 'max:40'], 'course_id' => ['nullable', 'integer', 'exists:courses,id'],
        ]);
        $this->svc->update($grade, $d);

        return redirect()->route('grades.index', ['class' => $grade->student->school_class_id, 'term' => $grade->term])->with('ok', 'Not güncellendi.');
    }

    public function destroy(Request $request, Grade $grade)
    {
        $this->ownedGrade($request, $grade);
        $grade->delete();

        return back()->with('ok', 'Not silindi.');
    }

    public function mine()
    {
        $student = auth()->user()->student ?? abort(403);
        $grades = Grade::with('course')->where('student_id', $student->id)->latest('exam_date')->get();

        return view('grades.mine', ['byTerm' => $grades->groupBy('term'), 'svc' => $this->svc]);
    }
}
