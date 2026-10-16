<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    private function authorizeOwner(Assignment $a): void
    {
        abort_unless(auth()->user()->hasRole('admin') || $a->created_by === auth()->id(), 403);
    }

    public function index()
    {
        $assignments = Assignment::with(['course', 'classes'])->withCount('submissions')
            ->when(! auth()->user()->hasRole('admin'), fn ($q) => $q->where('created_by', auth()->id()))
            ->latest()->paginate(20);

        return view('assignments.index', compact('assignments'));
    }

    public function create()
    {
        return view('assignments.form', [
            'assignment' => new Assignment(['type' => 'course', 'xp_reward' => 10]),
            'courses' => Course::where('is_active', true)->orderBy('name')->get(),
            'classes' => SchoolClass::orderBy('grade_level')->get(),
            'games' => config('games'),
        ]);
    }

    private function rules(): array
    {
        return [
            'type' => ['required', 'in:course,game'],
            'course_id' => ['required_if:type,course', 'nullable', 'exists:courses,id'],
            'game_slug' => ['required_if:type,game', 'nullable', 'in:'.implode(',', array_keys(config('games')))],
            'title' => ['required', 'string', 'max:160'],
            'details' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'level_from' => ['nullable', 'integer', 'min:1'],
            'level_to' => ['nullable', 'integer', 'gte:level_from'],
            'xp_reward' => ['required', 'integer', 'between:0,1000'],
            'classes' => ['required', 'array', 'min:1'],
            'classes.*' => ['exists:school_classes,id'],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $classes = $data['classes'];
        unset($data['classes']);
        $data['type'] === 'course' ? $data['game_slug'] = null : $data['course_id'] = null;

        $assignment = Assignment::create($data + ['created_by' => $request->user()->id]);
        $assignment->classes()->sync($classes);

        return redirect()->route('assignments.index')->with('ok', 'Ödev atandı.');
    }

    public function show(Assignment $assignment)
    {
        $this->authorizeOwner($assignment);
        $assignment->load('course', 'classes.students.user');
        $subs = $assignment->submissions()->get()->keyBy('student_id');

        return view('assignments.show', compact('assignment', 'subs'));
    }

    public function edit(Assignment $assignment)
    {
        $this->authorizeOwner($assignment);

        return view('assignments.form', [
            'assignment' => $assignment->load('classes'),
            'courses' => Course::where('is_active', true)->orderBy('name')->get(),
            'classes' => SchoolClass::orderBy('grade_level')->get(),
            'games' => config('games'),
        ]);
    }

    public function update(Request $request, Assignment $assignment)
    {
        $this->authorizeOwner($assignment);
        $data = $request->validate($this->rules());
        $classes = $data['classes'];
        unset($data['classes']);
        $data['type'] === 'course' ? $data['game_slug'] = null : $data['course_id'] = null;

        $assignment->update($data);
        $assignment->classes()->sync($classes);

        return redirect()->route('assignments.show', $assignment)->with('ok', 'Ödev güncellendi.');
    }

    public function destroy(Assignment $assignment)
    {
        $this->authorizeOwner($assignment);
        $assignment->delete();

        return redirect()->route('assignments.index')->with('ok', 'Ödev silindi.');
    }
}
