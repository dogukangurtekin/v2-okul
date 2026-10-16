<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Avatar;
use App\Models\Badge;
use App\Models\ClassBoardPost;
use App\Models\Student;
use App\Services\XpService;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    private function student(): Student
    {
        return auth()->user()->student ?? abort(403);
    }

    public function avatars()
    {
        $student = $this->student()->load('avatars');

        return view('student.avatars', [
            'student' => $student,
            'avatars' => Avatar::where('is_active', true)->orderBy('price')->get(),
            'owned' => $student->avatars->pluck('id')->all(),
        ]);
    }

    public function buyAvatar(Avatar $avatar, XpService $xp)
    {
        $student = $this->student();
        abort_unless($avatar->is_active, 404);

        if ($student->avatars()->whereKey($avatar->id)->exists()) {
            return back()->withErrors(['avatar' => 'Bu avatara zaten sahipsiniz.']);
        }
        if ($student->xp_total < $avatar->required_xp) {
            return back()->withErrors(['avatar' => "Bu avatar için toplam {$avatar->required_xp} XP gerekir."]);
        }
        if (! $xp->spend($student, $avatar->price, 'Avatar satın alındı: '.$avatar->name, $avatar)) {
            return back()->withErrors(['avatar' => 'Yeterli XP yok.']);
        }

        $student->avatars()->attach($avatar->id, ['unlocked_at' => now()]);

        return back()->with('ok', $avatar->name.' avatarı alındı!');
    }

    public function equipAvatar(Avatar $avatar)
    {
        $student = $this->student();
        abort_unless($student->avatars()->whereKey($avatar->id)->exists(), 403);
        $student->update(['current_avatar_id' => $avatar->id]);

        return back()->with('ok', 'Avatar seçildi.');
    }

    public function badges()
    {
        $student = $this->student()->load('badges');

        return view('student.badges', [
            'student' => $student,
            'badges' => Badge::orderBy('xp_threshold')->get(),
            'earned' => $student->badges->pluck('id')->all(),
        ]);
    }

    public function ranking()
    {
        $student = $this->student();
        $top = Student::with(['user', 'avatar'])->where('school_class_id', $student->school_class_id)
            ->orderByDesc('xp_total')->orderBy('id')->limit(20)->get();

        return view('student.ranking', compact('student', 'top'));
    }

    public function friends()
    {
        $student = $this->student();
        $friends = Student::with(['user', 'avatar'])->where('school_class_id', $student->school_class_id)
            ->where('id', '!=', $student->id)->get();

        return view('student.friends', compact('friends'));
    }

    public function classBoard()
    {
        $student = $this->student();
        $posts = ClassBoardPost::with('student.user', 'student.avatar')->where('school_class_id', $student->school_class_id)
            ->latest()->limit(50)->get();

        return view('student.board', ['posts' => $posts, 'messages' => config('board'), 'student' => $student]);
    }

    public function storeBoardPost(Request $request)
    {
        $student = $this->student();
        abort_unless($student->school_class_id, 403);
        $key = $request->validate(['message_key' => ['required', 'in:'.implode(',', array_keys(config('board')))]])['message_key'];

        // Spam korumasi: 10 sn icinde tek paylasim.
        if (ClassBoardPost::where('student_id', $student->id)->where('created_at', '>', now()->subSeconds(10))->exists()) {
            return back()->withErrors(['message_key' => 'Biraz bekleyip tekrar deneyin.']);
        }
        ClassBoardPost::create(['school_class_id' => $student->school_class_id, 'student_id' => $student->id, 'message_key' => $key]);

        return back()->with('ok', 'Paylaşıldı.');
    }

    /** Istemci her 30 sn'de bir cagirir; sunucu en fazla 60 sn ekler ve sureyi birikimli tutar. */
    public function pingTime(Request $request)
    {
        $student = $this->student();
        $last = $student->last_seen_at;
        $delta = $last ? min(60, max(0, (int) $last->diffInSeconds(now()))) : 0;
        $student->increment('total_seconds', min($delta, 60) ?: 0);
        $student->forceFill(['last_seen_at' => now()])->saveQuietly();

        return response()->json(['ok' => true]);
    }
}
