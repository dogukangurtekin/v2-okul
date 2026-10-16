<?php

namespace App\Http\Controllers;

use App\Models\LoginToken;
use App\Models\Student;
use App\Models\User;
use App\Services\LoginTokenService;
use App\Support\StaffScope;
use Illuminate\Http\Request;

class QrLoginController extends Controller
{
    public function __construct(private LoginTokenService $tokens) {}

    private function loginAs(Request $request, User $user)
    {
        auth()->login($user);
        $request->session()->regenerate();
        $request->session()->put('login_at', now()->timestamp + 1);
        $user->forceFill(['last_login_at' => now()])->save();
    }

    // ---- Kart: GET onay sayfasi (link onizleyicileri tokeni yakmasin), POST ile giris ----

    public function showCard(string $token)
    {
        $t = $this->tokens->find($token, 'card');
        abort_unless($t && $t->usable(), 404, 'Bu giriş kartı geçersiz veya süresi dolmuş.');

        return view('qr.card', ['name' => $t->user->name, 'token' => $token]);
    }

    public function useCard(Request $request, string $token)
    {
        $t = $this->tokens->find($token, 'card');
        $user = $t ? $this->tokens->consume($t) : null;
        abort_unless($user, 404, 'Bu giriş kartı geçersiz veya süresi dolmuş.');
        $this->loginAs($request, $user);

        return redirect()->route('dashboard');
    }

    // ---- Giris ekraninda QR (misafir tarayici) ----

    public function start(Request $request)
    {
        [$token, $poll] = $this->tokens->startQr($request);

        return response()->json(['url' => route('qr.approve', $token), 'token' => $token, 'poll' => $poll, 'ttl' => LoginTokenService::QR_MINUTES * 60]);
    }

    public function status(Request $request, string $token)
    {
        $t = $this->tokens->find($token, 'qr');
        if (! $t || $t->poll_hash !== LoginToken::hash((string) $request->query('poll')) || ! $t->usable()) {
            return response()->json(['status' => 'expired']);
        }

        return response()->json(['status' => $t->approved_at ? 'approved' : 'pending']);
    }

    public function complete(Request $request, string $token)
    {
        $t = $this->tokens->find($token, 'qr');
        abort_unless($t && $t->approved_at && $t->poll_hash === LoginToken::hash((string) $request->input('poll')), 403);
        $user = $this->tokens->consume($t);
        abort_unless($user, 403);
        $this->loginAs($request, $user);

        return response()->json(['redirect' => route('dashboard')]);
    }

    // ---- Personel onayi ----

    public function approveForm(Request $request, string $token)
    {
        $t = $this->tokens->find($token, 'qr');
        abort_unless($t && $t->usable() && ! $t->approved_at, 404, 'QR kodu geçersiz veya süresi dolmuş.');

        return view('qr.approve', [
            't' => $t, 'token' => $token,
            'students' => StaffScope::students($request->user())->with('user', 'schoolClass')->get()->sortBy(fn ($s) => $s->user->name)->values(),
        ]);
    }

    public function approve(Request $request, string $token)
    {
        $t = $this->tokens->find($token, 'qr');
        abort_unless($t && $t->usable() && ! $t->approved_at, 404);
        $studentId = $request->validate(['student_id' => ['required', 'integer']])['student_id'];
        $student = StaffScope::students($request->user())->with('user')->findOrFail($studentId);
        abort_unless($student->user->is_active, 422, 'Öğrenci hesabı pasif.');

        $this->tokens->approve($t, $student->user, $request->user());

        return redirect()->route('dashboard')->with('ok', $student->user->name.' için giriş onaylandı.');
    }
}
