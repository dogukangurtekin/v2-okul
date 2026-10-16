<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseSlide;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    private function rules(?Course $course = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', Rule::unique('courses', 'code')->ignore($course)],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:80'],
            'difficulty' => ['nullable', 'string', 'max:30'],
            'parent_id' => ['nullable', 'exists:courses,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'cover' => ['nullable', 'image', 'max:4096'],
        ];
    }

    private function scoped()
    {
        $user = auth()->user();

        return Course::query()->when(! $user->hasRole('admin'), fn ($q) => $q->where(fn ($w) => $w
            ->where('created_by', $user->id)->orWhere('teacher_id', $user->teacher?->id)));
    }

    public function index()
    {
        $courses = $this->scoped()->withCount(['slides', 'classes'])->orderBy('sort_order')->orderBy('name')->paginate(20);

        return view('courses.index', compact('courses'));
    }

    public function create()
    {
        return view('courses.form', ['course' => new Course(['is_active' => true]), 'parents' => Course::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['is_active'] = $request->boolean('is_active');
        $data['created_by'] = $request->user()->id;
        $data['teacher_id'] = $request->user()->teacher?->id;
        $data['sort_order'] ??= 0;
        if ($request->hasFile('cover')) {
            $data['cover_path'] = $request->file('cover')->store('covers', 'public');
        }
        unset($data['cover']);

        $course = Course::create($data);

        return redirect()->route('courses.edit', $course)->with('ok', 'Ders oluşturuldu, slayt ekleyebilirsiniz.');
    }

    public function edit(Course $course)
    {
        $this->authorize('manage', $course);

        return view('courses.form', [
            'course' => $course->load('slides', 'classes'),
            'parents' => Course::where('id', '!=', $course->id)->orderBy('name')->get(),
            'classes' => SchoolClass::orderBy('grade_level')->get(),
        ]);
    }

    public function update(Request $request, Course $course)
    {
        $this->authorize('manage', $course);
        $data = $request->validate($this->rules($course));
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] ??= 0;
        if ($request->hasFile('cover')) {
            $course->cover_path && Storage::disk('public')->delete($course->cover_path);
            $data['cover_path'] = $request->file('cover')->store('covers', 'public');
        }
        unset($data['cover']);

        $course->update($data);

        return back()->with('ok', 'Ders güncellendi.');
    }

    public function destroy(Course $course)
    {
        $this->authorize('manage', $course);
        $course->delete();

        return redirect()->route('courses.index')->with('ok', 'Ders silindi.');
    }

    public function assignClasses(Request $request, Course $course)
    {
        $this->authorize('manage', $course);
        $ids = $request->validate(['classes' => ['array'], 'classes.*' => ['exists:school_classes,id']])['classes'] ?? [];
        $course->classes()->sync($ids);

        return back()->with('ok', 'Sınıf atamaları kaydedildi.');
    }

    public function preview(Course $course)
    {
        $this->authorize('manage', $course);

        return view('courses.player', ['course' => $course->load('slides'), 'progress' => null]);
    }

    public function export(Course $course)
    {
        $this->authorize('manage', $course);
        $payload = [
            'format' => 'v2-okul.course', 'version' => 1,
            'course' => $course->only(['name', 'code', 'description', 'category', 'difficulty', 'curriculum']),
            'slides' => $course->slides->map->only(['position', 'type', 'title', 'content'])->all(),
        ];

        return response()->json($payload, 200, [
            'Content-Disposition' => 'attachment; filename="'.$course->code.'.course.json"',
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /** Tek veya çok dosya: v2 JSON paketi ya da v1 .coursepkg (otomatik ayrılır). */
    public function import(Request $request, \App\Services\CoursePkgImporter $v1)
    {
        $request->validate(['files' => ['nullable', 'array', 'max:20'], 'files.*' => ['file', 'max:20480'], 'file' => ['nullable', 'file', 'max:20480']]);
        $files = array_filter(array_merge((array) $request->file('files'), [$request->file('file')]));
        abort_if(! $files, 422, 'Dosya seçilmedi.');

        $report = ['courses' => [], 'warnings' => [], 'errors' => []];
        $last = null;
        foreach ($files as $f) {
            $raw = (string) file_get_contents($f->getRealPath());
            $json = str_starts_with($raw, 'COURSEPKG2') ? null : json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $raw), true);

            if (is_array($json) && \App\Services\CoursePkgReader::isV2($json)) {
                $last = $this->importV2($json, $request);
                $report['courses'][] = ['id' => $last->id, 'name' => $last->name, 'code' => $last->code, 'slides' => $last->slides()->count(), 'quizzes' => 0, 'sub' => false, 'v1_slides' => null];
                continue;
            }
            $r = $v1->import($raw, $request->user(), $f->getClientOriginalName());
            foreach (['courses', 'warnings', 'errors'] as $k) {
                $report[$k] = array_merge($report[$k], $r[$k]);
            }
        }

        if (count($report['courses']) === 1 && ! $report['warnings'] && ! $report['errors']) {
            return redirect()->route('courses.edit', $report['courses'][0]['id'])->with('ok', 'Ders içe aktarıldı.');
        }

        return redirect()->route('courses.index')->with('import_report', $report);
    }

    private function importV2(array $data, Request $request): Course
    {
        return DB::transaction(function () use ($data, $request) {
            $base = $data['course']['code'] ?? 'IMPORT';
            $code = $base;
            for ($i = 2; Course::where('code', $code)->exists(); $i++) {
                $code = $base.'-'.$i;
            }
            $course = Course::create([
                'name' => $data['course']['name'] ?? 'İçe aktarılan ders',
                'code' => $code,
                'description' => $data['course']['description'] ?? null,
                'created_by' => $request->user()->id,
                'teacher_id' => $request->user()->teacher?->id,
            ]);
            foreach ($data['slides'] ?? [] as $i => $s) {
                $course->slides()->create([
                    'position' => $s['position'] ?? $i,
                    'type' => array_key_exists($s['type'] ?? '', CourseSlide::TYPES) ? $s['type'] : 'text',
                    'title' => $s['title'] ?? null,
                    'content' => $s['content'] ?? null,
                ]);
            }

            return $course;
        });
    }
}
