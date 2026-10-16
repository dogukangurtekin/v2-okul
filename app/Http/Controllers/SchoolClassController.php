<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Teacher;
use Illuminate\Http\Request;

class SchoolClassController extends Controller
{
    private function rules(?SchoolClass $class = null): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'section' => ['required', 'string', 'max:50'],
            'grade_level' => ['required', 'integer', 'between:1,12'],
            'academic_year' => ['required', 'string', 'max:20'],
            'teacher_id' => ['nullable', 'exists:teachers,id'],
        ];
    }

    public function index()
    {
        $classes = SchoolClass::with('teacher.user')->withCount('students')->orderBy('grade_level')->orderBy('section')->paginate(20);

        return view('classes.index', compact('classes'));
    }

    public function create()
    {
        return view('classes.form', ['class' => new SchoolClass(['academic_year' => date('Y').'-'.(date('Y') + 1)]), 'teachers' => Teacher::with('user')->get()]);
    }

    public function store(Request $request)
    {
        SchoolClass::create($request->validate($this->rules()));

        return redirect()->route('classes.index')->with('ok', 'Sınıf oluşturuldu.');
    }

    public function edit(SchoolClass $class)
    {
        return view('classes.form', ['class' => $class, 'teachers' => Teacher::with('user')->get()]);
    }

    public function update(Request $request, SchoolClass $class)
    {
        $class->update($request->validate($this->rules($class)));

        return redirect()->route('classes.index')->with('ok', 'Sınıf güncellendi.');
    }

    public function destroy(SchoolClass $class)
    {
        $class->delete();

        return back()->with('ok', 'Sınıf silindi.');
    }
}
