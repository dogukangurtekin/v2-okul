<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\LiveSession;
use App\Services\LiveSessionService;
use Illuminate\Http\Request;

class LiveController extends Controller
{
    public function __construct(private LiveSessionService $live) {}

    private function student()
    {
        return auth()->user()->student ?? abort(403);
    }

    public function joinForm()
    {
        $student = $this->student();
        $open = LiveSession::where('status', '!=', 'finished')->where('school_class_id', $student->school_class_id)->latest()->get();

        return view('student.live-join', ['open' => $open]);
    }

    public function join(Request $request)
    {
        $code = strtoupper(trim($request->validate(['code' => ['required', 'string', 'max:8']])['code']));
        $session = LiveSession::where('join_code', $code)->first();
        $p = $session ? $this->live->join($this->student(), $session) : null;

        if (! $p) {
            return back()->withErrors(['code' => 'Oturum bulunamadı, kilitli ya da size kapalı.'])->withInput();
        }

        return redirect()->route('student.live.play', $session);
    }

    public function joinById(LiveSession $session)
    {
        abort_unless($this->live->join($this->student(), $session), 403);

        return redirect()->route('student.live.play', $session);
    }

    public function play(LiveSession $session)
    {
        $me = $session->participants()->where('student_id', $this->student()->id)->firstOrFail();

        return view('student.live-play', ['session' => $session, 'me' => $me]);
    }

    public function status(LiveSession $session)
    {
        $me = $session->participants()->where('student_id', $this->student()->id)->firstOrFail();

        return response()->json($this->live->snapshot($session, true, $me));
    }

    public function answer(Request $request, LiveSession $session)
    {
        $me = $session->participants()->where('student_id', $this->student()->id)->firstOrFail();
        $d = $request->validate(['question' => ['required', 'integer', 'min:0'], 'answer' => ['required', 'integer', 'min:0', 'max:9']]);

        return response()->json($this->live->answer($me, $d['question'], $d['answer']));
    }

    public function typing(Request $request, LiveSession $session)
    {
        $me = $session->participants()->where('student_id', $this->student()->id)->firstOrFail();
        $this->live->typing($me, (string) $request->input('typed', ''));

        return response()->json(['ok' => true]);
    }
}
