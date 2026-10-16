<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $students = Student::with(['user', 'schoolClass'])
            ->when($request->filled('class'), fn ($q) => $q->where('school_class_id', $request->integer('class')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('student_no', 'like', '%'.$request->q.'%')
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$request->q.'%'))))
            ->latest()->paginate(20)->withQueryString();

        return view('students.index', ['students' => $students, 'classes' => SchoolClass::orderBy('grade_level')->get()]);
    }

    public function create()
    {
        return view('students.form', ['student' => new Student, 'classes' => SchoolClass::orderBy('grade_level')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'student_no' => ['required', 'string', 'max:50', 'unique:students,student_no'],
            'school_class_id' => ['nullable', 'exists:school_classes,id'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:20'],
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'role_id' => Role::where('slug', Role::STUDENT)->value('id'),
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
            $user->student()->create(Arr::except($data, ['name', 'email', 'password']));
        });

        return redirect()->route('students.index')->with('ok', 'Öğrenci eklendi.');
    }

    public function edit(Student $student)
    {
        return view('students.form', ['student' => $student->load('user'), 'classes' => SchoolClass::orderBy('grade_level')->get()]);
    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$student->user_id],
            'password' => ['nullable', 'string', 'min:6'],
            'student_no' => ['required', 'string', 'max:50', 'unique:students,student_no,'.$student->id],
            'school_class_id' => ['nullable', 'exists:school_classes,id'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:20'],
        ]);

        DB::transaction(function () use ($data, $student) {
            $student->user->update(array_filter([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'] ?? null,
            ]));
            $student->update(Arr::except($data, ['name', 'email', 'password']));
        });

        return redirect()->route('students.index')->with('ok', 'Öğrenci güncellendi.');
    }

    public function destroy(Student $student)
    {
        $student->user->delete(); // students satırı cascade ile silinir

        return back()->with('ok', 'Öğrenci silindi.');
    }
}
