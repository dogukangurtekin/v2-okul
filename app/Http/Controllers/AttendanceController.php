<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\SchoolClass;
use App\Services\AttendanceService;
use App\Support\StaffScope;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $svc) {}

    private function classFor(Request $request, int $id): SchoolClass
    {
        return StaffScope::classes($request->user())->findOrFail($id);
    }

    /** Yoklama giris sayfasi. */
    public function sheet(Request $request)
    {
        $classes = StaffScope::classes($request->user())->get();
        $date = $request->input('date', today()->toDateString());
        $class = $request->filled('class') ? $this->classFor($request, $request->integer('class')) : null;
        $courseId = $request->integer('course') ?: null;

        $students = collect();
        $existing = collect();
        if ($class) {
            $students = $class->students()->with('user')->get()->sortBy(fn ($s) => $s->user->name)->values();
            $existing = Attendance::whereIn('student_id', $students->pluck('id'))->whereDate('date', $date)->where('course_key', $courseId ?? 0)->get()->keyBy('student_id');
        }

        return view('attendance.sheet', [
            'classes' => $classes, 'class' => $class, 'date' => $date, 'courseId' => $courseId, 'students' => $students, 'existing' => $existing,
            'courses' => $class ? Course::whereHas('classes', fn ($q) => $q->where('school_classes.id', $class->id))->orderBy('name')->get() : collect(),
        ]);
    }

    public function save(Request $request)
    {
        $d = $request->validate([
            'school_class_id' => ['required', 'integer'],
            'date' => ['required', 'date', 'before_or_equal:today', 'after:'.today()->subYear()->toDateString()],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'rows' => ['required', 'array', 'max:200'],
            'rows.*.status' => ['required', 'in:'.implode(',', array_keys(config('school.attendance_statuses')))],
            'rows.*.note' => ['nullable', 'string', 'max:200'],
        ]);
        $class = $this->classFor($request, $d['school_class_id']);
        $n = $this->svc->saveSheet($class->students()->pluck('id'), $d['date'], $d['course_id'] ?? null, $d['rows'], $request->user());

        return redirect()->route('attendance.sheet', ['class' => $class->id, 'date' => $d['date'], 'course' => $d['course_id'] ?? null])->with('ok', "$n öğrencinin yoklaması kaydedildi.");
    }

    private function reportData(Request $request): array
    {
        $class = $request->filled('class') ? $this->classFor($request, $request->integer('class')) : null;
        $from = $request->input('from', today()->startOfMonth()->toDateString());
        $to = $request->input('to', today()->toDateString());
        $students = $class ? $class->students()->with('user')->get()->sortBy(fn ($s) => $s->user->name)->values() : collect();

        return [$class, $from, $to, $students, $students->isNotEmpty() ? $this->svc->summary($students->pluck('id'), $from, $to) : []];
    }

    public function report(Request $request)
    {
        [$class, $from, $to, $students, $sum] = $this->reportData($request);

        return view('attendance.report', ['classes' => StaffScope::classes($request->user())->get(), 'class' => $class, 'from' => $from, 'to' => $to, 'students' => $students, 'sum' => $sum]);
    }

    public function export(Request $request)
    {
        [$class, $from, $to, $students, $sum] = $this->reportData($request);
        abort_unless($class, 404);

        return response()->streamDownload(function () use ($students, $sum) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Öğrenci', 'No', 'Geldi', 'Geç', 'İzinli', 'Gelmedi', 'Devam %'], ';');
            foreach ($students as $s) {
                $a = $sum[$s->id];
                fputcsv($out, [$s->user->name, $s->student_no, $a['present'], $a['late'], $a['excused'], $a['absent'], $a['rate'] ?? ''], ';');
            }
            fclose($out);
        }, 'yoklama-'.$class->label.'-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Ogrenci: kendi devamsizligi. */
    public function mine()
    {
        $student = auth()->user()->student ?? abort(403);
        $records = Attendance::with('course')->where('student_id', $student->id)->latest('date')->limit(60)->get();

        return view('attendance.mine', ['records' => $records, 'sum' => $this->svc->summary(collect([$student->id]))[$student->id]]);
    }
}
