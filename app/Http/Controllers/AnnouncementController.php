<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Role;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /** Herkes: kendi hedef kitlesine yayimlanmis duyurular. */
    public function feed(Request $request)
    {
        return view('announcements.feed', ['items' => Announcement::visibleTo($request->user())->with('author')->paginate(15)]);
    }

    public function index()
    {
        return view('announcements.index', ['items' => Announcement::with('author')->latest()->paginate(20)]);
    }

    public function create()
    {
        return view('announcements.form', ['a' => new Announcement(['audience' => 'all', 'published_at' => now()])]);
    }

    private function rules(): array
    {
        return ['title' => ['required', 'string', 'max:200'], 'content' => ['required', 'string', 'max:5000'],
            'audience' => ['required', 'in:'.implode(',', array_keys(Announcement::AUDIENCES))], 'published_at' => ['nullable', 'date'], 'notify' => ['nullable', 'boolean']];
    }

    public function store(Request $request, NotificationService $notify)
    {
        $d = $request->validate($this->rules());
        $a = Announcement::create([
            'title' => $d['title'], 'content' => $d['content'], 'audience' => $d['audience'],
            'published_by' => $request->user()->id, 'published_at' => $d['published_at'] ?? now(),
        ]);

        // Hemen yayimlanan duyuru icin istenirse hedef kitleye bildirim gonder.
        if ($request->boolean('notify') && $a->published_at->lte(now())) {
            $roles = match ($a->audience) { 'students' => [Role::STUDENT], 'teachers' => [Role::TEACHER], default => [Role::STUDENT, Role::TEACHER] };
            $ids = User::whereHas('role', fn ($q) => $q->whereIn('slug', $roles))->where('is_active', true)->pluck('id');
            $notify->send($request->user(), 'Duyuru: '.$a->title, mb_substr($a->content, 0, 140), '/duyurular', $ids);
        }

        return redirect()->route('announcements.index')->with('ok', 'Duyuru kaydedildi.');
    }

    public function edit(Announcement $announcement)
    {
        return view('announcements.form', ['a' => $announcement]);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $d = $request->validate($this->rules());
        $announcement->update(['title' => $d['title'], 'content' => $d['content'], 'audience' => $d['audience'], 'published_at' => $d['published_at'] ?? $announcement->published_at]);

        return redirect()->route('announcements.index')->with('ok', 'Duyuru güncellendi.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return back()->with('ok', 'Duyuru silindi.');
    }
}
