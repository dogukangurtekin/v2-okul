<?php

namespace App\Http\Controllers;

use App\Services\BulkImportService;
use Illuminate\Http\Request;
use RuntimeException;

class BulkImportController extends Controller
{
    public function index()
    {
        return view('import.index');
    }

    public function template(string $type)
    {
        abort_unless(isset(BulkImportService::TEMPLATES[$type]), 404);

        return response()->streamDownload(function () use ($type) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // Excel UTF-8 icin BOM
            foreach (BulkImportService::TEMPLATES[$type] as $row) {
                fputcsv($out, $row, ';');
            }
            fclose($out);
        }, "$type-sablon.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function upload(Request $request, string $type, BulkImportService $svc)
    {
        abort_unless(in_array($type, ['student', 'teacher'], true), 404);
        $request->validate(['file' => ['required', 'file', 'max:2048']]);

        try {
            $rows = $svc->parse($request->file('file'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }
        $result = $type === 'student' ? $svc->importStudents($rows, $request->boolean('create_classes')) : $svc->importTeachers($rows);

        // Gecici sifreler yalnizca bu yanitta gosterilir; veritabaninda duz metin tutulmaz.
        return response()->view('import.result', ['created' => $result['created'], 'rowErrors' => $result['errors'], 'type' => $type, 'total' => count($rows)])->header('Cache-Control', 'no-store');
    }
}
