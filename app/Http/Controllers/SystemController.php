<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SystemController extends Controller
{
    public function index()
    {
        return view('system.index', ['maintenance' => Setting::get('maintenance') === '1', 'message' => Setting::get('maintenance_message', '')]);
    }

    public function toggleMaintenance(Request $request)
    {
        $d = $request->validate(['message' => ['nullable', 'string', 'max:300']]);
        $on = Setting::get('maintenance') !== '1';
        Setting::put('maintenance', $on ? '1' : '0');
        Setting::put('maintenance_message', $d['message'] ?? '');

        return back()->with('ok', $on ? 'Bakım modu AÇIK: yönetici dışındakiler siteye giremez.' : 'Bakım modu kapatıldı.');
    }

    /** Ogrenci ilerleme verilerini siler (kullanici, sinif, ders ve oyun tanimlari kalir). */
    public function resetStudentData(Request $request)
    {
        $request->validate(['confirm' => ['required', 'in:SIFIRLA'], 'password' => ['required', 'string']]);
        if (! Hash::check($request->password, $request->user()->password)) {
            return back()->withErrors(['password' => 'Şifre hatalı.']);
        }

        DB::transaction(function () {
            foreach (['xp_ledger', 'student_avatar', 'student_badge', 'class_board_posts', 'course_progress', 'assignment_submissions', 'game_progress', 'student_activity_logs', 'live_answers', 'live_participants', 'student_messages'] as $t) {
                DB::table($t)->delete();
            }
            DB::table('live_sessions')->delete();
            DB::table('students')->update(['xp_balance' => 0, 'xp_total' => 0, 'current_avatar_id' => null, 'total_seconds' => 0, 'last_seen_at' => null, 'last_path' => null]);
        });

        return back()->with('ok', 'Tüm öğrenci ilerleme verileri sıfırlandı.');
    }
}
