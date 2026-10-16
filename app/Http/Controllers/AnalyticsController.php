<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\AnalyticsService;
use App\Support\StaffScope;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    private function load(Request $request, AnalyticsService $svc): array
    {
        $students = StaffScope::students($request->user())->with(['user', 'schoolClass'])
            ->when($request->filled('class'), fn ($q) => $q->where('school_class_id', $request->integer('class')))
            ->get()->sortBy(fn ($s) => $s->user->name)->values();

        return [$students, $svc->forStudents($students)];
    }

    public function index(Request $request, AnalyticsService $svc)
    {
        [$students, $stats] = $this->load($request, $svc);
        $scores = collect($stats)->pluck('score')->filter(fn ($v) => $v !== null);

        return view('analytics.index', [
            'students' => $students, 'stats' => $stats, 'classes' => StaffScope::classes($request->user())->get(),
            'avg' => $scores->isNotEmpty() ? (int) round($scores->avg()) : null,
            'atRisk' => $students->filter(fn ($s) => ($stats[$s->id]['score'] ?? 100) < 40)->count(),
        ]);
    }

    public function export(Request $request, AnalyticsService $svc)
    {
        [$students, $stats] = $this->load($request, $svc);
        $head = ['Öğrenci', 'Sınıf', 'Başarı %', 'Toplam XP', 'Ödev', 'Ders', 'Oyun seviyesi', 'En iyi KDK', 'Devam %', 'Not ort.', 'Dakika', 'Son görülme'];

        return response()->streamDownload(function () use ($students, $stats, $head) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // Excel icin UTF-8 BOM
            fputcsv($out, $head, ';');
            foreach ($students as $s) {
                $a = $stats[$s->id];
                fputcsv($out, [$s->user->name, $s->schoolClass?->label, $a['score'] ?? '', $a['xp_total'], "{$a['assignments_done']}/{$a['assignments_total']}",
                    "{$a['courses_done']}/{$a['courses_total']}", $a['game_levels'], $a['best_wpm'], $a['attendance_rate'] ?? '', $a['grade_avg'] ?? '', $a['minutes'], $a['last_seen']?->format('d.m.Y H:i')], ';');
            }
            fclose($out);
        }, 'ogrenci-analitik-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
