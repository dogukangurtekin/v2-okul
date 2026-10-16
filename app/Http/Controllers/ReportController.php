<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ParentMessage;
use App\Models\Student;
use App\Services\AnalyticsService;
use App\Services\ParentReportService;
use App\Support\StaffScope;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private function data(Student $student, AnalyticsService $svc): array
    {
        $student->load('user', 'schoolClass', 'badges', 'avatar');
        $assignments = Assignment::whereHas('classes', fn ($q) => $q->where('school_classes.id', $student->school_class_id))->latest()->limit(15)->get();
        $subs = AssignmentSubmission::where('student_id', $student->id)->get()->keyBy('assignment_id');

        return ['student' => $student, 'stats' => $svc->forStudents(collect([$student]))[$student->id], 'assignments' => $assignments, 'subs' => $subs, 'parent' => false];
    }

    /** Ogretmen/yonetici karnesi. */
    public function show(Request $request, Student $student, AnalyticsService $svc, ParentReportService $parents)
    {
        StaffScope::authorizeStudent($request->user(), $student);

        return view('reports.card', $this->data($student, $svc) + [
            'parentLink' => $parents->link($student),
            'messages' => ParentMessage::where('student_id', $student->id)->latest()->limit(5)->get(),
        ]);
    }

    /** Veli: giris yok, imzali baglanti. */
    public function parent(Student $student, AnalyticsService $svc)
    {
        return view('reports.card', $this->data($student, $svc) + ['parent' => true, 'parentLink' => null, 'messages' => collect()]);
    }

    /** Ogrencinin kendi karnesi. */
    public function mine(AnalyticsService $svc)
    {
        return view('reports.card', $this->data(auth()->user()->student ?? abort(403), $svc) + ['parent' => true, 'parentLink' => null, 'messages' => collect()]);
    }

    public function sendToParent(Request $request, Student $student, ParentReportService $parents)
    {
        StaffScope::authorizeStudent($request->user(), $student);
        $m = $parents->send($student->load('user'), $request->user());

        return back()->with('ok', 'Veli bildirimi: '.['sent' => 'gönderildi', 'logged' => 'kaydedildi (kanal: log)', 'failed' => 'başarısız', 'no_phone' => 'veli telefonu yok'][$m->status]);
    }

    /** Sinif icin veli numarasi + baglanti listesi (CSV). */
    public function parentList(Request $request, ParentReportService $parents)
    {
        $students = StaffScope::students($request->user())->with('user', 'schoolClass')
            ->when($request->filled('class'), fn ($q) => $q->where('school_class_id', $request->integer('class')))->get();

        return response()->streamDownload(function () use ($students, $parents) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Öğrenci', 'Sınıf', 'Veli', 'Telefon', 'Rapor bağlantısı'], ';');
            foreach ($students as $s) {
                fputcsv($out, [$s->user->name, $s->schoolClass?->label, $s->parent_name, $parents->phone($s->parent_phone) ?? 'GEÇERSİZ', $parents->link($s)], ';');
            }
            fclose($out);
        }, 'veli-listesi.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
