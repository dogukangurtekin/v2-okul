<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\XpService;
use Illuminate\Http\Request;

/** Ogretmen/yonetici XP hediyesi (v1: dashboard/xp-hediyesi). */
class XpGrantController extends Controller
{
    public function store(Request $request, XpService $xp)
    {
        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1', 'max:200'],
            'student_ids.*' => ['exists:students,id'],
            'amount' => ['required', 'integer', 'between:1,500'],
            'reason' => ['required', 'string', 'max:120'],
        ]);

        foreach (Student::whereIn('id', $data['student_ids'])->get() as $student) {
            $xp->award($student, $data['amount'], $data['reason'], null, $request->user()->id);
        }

        return back()->with('ok', count($data['student_ids']).' öğrenciye XP verildi.');
    }
}
