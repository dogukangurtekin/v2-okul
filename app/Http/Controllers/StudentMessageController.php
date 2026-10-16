<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\StudentMessage;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class StudentMessageController extends Controller
{
    public function __construct(private NotificationService $notify) {}

    public function studentIndex()
    {
        $student = auth()->user()->student ?? abort(403);
        $messages = StudentMessage::where('student_id', $student->id)->latest()->limit(50)->get();
        StudentMessage::where('student_id', $student->id)->whereNotNull('replied_at')->whereNull('student_read_at')->update(['student_read_at' => now()]);
        $teacherUser = $student->schoolClass?->teacher?->user;

        return view('messages.student', ['messages' => $messages, 'teacherUser' => $teacherUser]);
    }

    public function studentSend(Request $request)
    {
        $student = $request->user()->student ?? abort(403);
        $d = $request->validate(['to' => ['required', 'in:teacher,admin'], 'body' => ['required', 'string', 'max:500']]);

        $recipient = null;
        if ($d['to'] === 'teacher') {
            $recipient = $student->schoolClass?->teacher?->user;
            if (! $recipient) {
                return back()->withErrors(['to' => 'Sınıfınıza atanmış öğretmen yok; yöneticiye yazabilirsiniz.']);
            }
        }
        $m = StudentMessage::create(['student_id' => $student->id, 'recipient_type' => $d['to'], 'recipient_user_id' => $recipient?->id, 'body' => $d['body']]);

        $to = $recipient ? collect([$recipient->id]) : User::whereHas('role', fn ($q) => $q->where('slug', Role::ADMIN))->pluck('id');
        $this->notify->send($request->user(), 'Öğrenci mesajı', $request->user()->name.': '.mb_substr($d['body'], 0, 100), '/ogrenci-mesajlari', $to);

        return back()->with('ok', 'Mesajın gönderildi.');
    }

    private function staffQuery()
    {
        $u = auth()->user();

        return StudentMessage::with('student.user', 'student.schoolClass')->whereNull('staff_deleted_at')
            ->when($u->hasRole('admin'), fn ($q) => $q->where('recipient_type', 'admin'), fn ($q) => $q->where('recipient_type', 'teacher')->where('recipient_user_id', $u->id));
    }

    public function staffIndex()
    {
        return view('messages.staff', ['messages' => $this->staffQuery()->orderByRaw('replied_at IS NOT NULL')->latest()->paginate(20)]);
    }

    public function staffReply(Request $request, StudentMessage $message)
    {
        abort_unless($this->staffQuery()->whereKey($message->id)->exists(), 403);
        $body = $request->validate(['reply' => ['required', 'string', 'max:500']])['reply'];
        $message->update(['reply_body' => $body, 'replied_by' => $request->user()->id, 'replied_at' => now(), 'student_read_at' => null]);

        $this->notify->send($request->user(), 'Mesajına yanıt geldi', mb_substr($body, 0, 120), '/ogrenci/mesajlar', [$message->student->user_id]);

        return back()->with('ok', 'Yanıt gönderildi.');
    }

    public function staffDestroy(StudentMessage $message)
    {
        abort_unless($this->staffQuery()->whereKey($message->id)->exists(), 403);
        $message->update(['staff_deleted_at' => now()]);

        return back()->with('ok', 'Mesaj gizlendi.');
    }

    public function staffClearAnswered()
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $n = $this->staffQuery()->whereNotNull('replied_at')->update(['staff_deleted_at' => now()]);

        return back()->with('ok', "$n yanıtlanmış mesaj gizlendi.");
    }
}
