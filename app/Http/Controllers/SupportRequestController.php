<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\SupportReply;
use App\Models\SupportRequest;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class SupportRequestController extends Controller
{
    public function __construct(private NotificationService $notify) {}

    private function admin(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    private function authorizeAccess(SupportRequest $r): void
    {
        abort_unless($this->admin() || $r->sender_user_id === auth()->id(), 403);
    }

    private function adminIds()
    {
        return User::whereHas('role', fn ($q) => $q->where('slug', Role::ADMIN))->where('is_active', true)->pluck('id');
    }

    public function index(Request $request)
    {
        $archived = $request->boolean('archive');
        $items = SupportRequest::with('sender')
            ->when(! $this->admin(), fn ($q) => $q->where('sender_user_id', auth()->id()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($archived, fn ($q) => $q->whereNotNull('archived_at'), fn ($q) => $q->whereNull('archived_at'))
            ->latest()->paginate(20)->withQueryString();

        return view('support.index', compact('items', 'archived'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('teacher'), 403);
        $d = $request->validate([
            'subject' => ['required', 'string', 'max:160'], 'message' => ['required', 'string', 'max:3000'],
            'category' => ['required', 'in:'.implode(',', array_keys(SupportRequest::CATEGORIES))], 'priority' => ['required', 'in:low,normal,high'],
        ]);
        $r = SupportRequest::create($d + ['sender_user_id' => $request->user()->id]);
        $this->notify->send($request->user(), 'Yeni destek talebi', $r->subject, route('support.show', $r, false), $this->adminIds());

        return redirect()->route('support.show', $r)->with('ok', 'Talebiniz iletildi.');
    }

    public function show(SupportRequest $supportRequest)
    {
        $this->authorizeAccess($supportRequest);
        if ($this->admin() && ! $supportRequest->read_at) {
            $supportRequest->update(['read_at' => now()]);
        }
        $replies = $supportRequest->replies()->with('sender')->when(! $this->admin(), fn ($q) => $q->where('internal_note', false))->get();

        return view('support.show', ['r' => $supportRequest->load('sender'), 'replies' => $replies]);
    }

    public function reply(Request $request, SupportRequest $supportRequest)
    {
        $this->authorizeAccess($supportRequest);
        abort_if($supportRequest->status === 'closed', 422, 'Talep kapalı.');
        $d = $request->validate(['message' => ['required', 'string', 'max:3000'], 'internal_note' => ['nullable', 'boolean']]);

        $internal = $this->admin() && $request->boolean('internal_note');
        SupportReply::create(['support_request_id' => $supportRequest->id, 'sender_user_id' => $request->user()->id, 'message' => $d['message'], 'internal_note' => $internal]);

        if (! $internal) {
            $supportRequest->update(['status' => $this->admin() ? 'answered' : 'open']);
            $to = $this->admin() ? collect([$supportRequest->sender_user_id])->filter() : $this->adminIds();
            if ($to->isNotEmpty()) {
                $this->notify->send($request->user(), 'Talebinize yanıt: '.$supportRequest->subject, mb_substr($d['message'], 0, 140), route('support.show', $supportRequest, false), $to);
            }
        }

        return back()->with('ok', 'Yanıt kaydedildi.');
    }

    public function close(SupportRequest $supportRequest)
    {
        $this->authorizeAccess($supportRequest);
        $supportRequest->update(['status' => 'closed', 'closed_at' => now()]);

        return back()->with('ok', 'Talep kapatıldı.');
    }

    public function archive(SupportRequest $supportRequest)
    {
        abort_unless($this->admin(), 403);
        $supportRequest->update(['archived_at' => $supportRequest->archived_at ? null : now()]);

        return redirect()->route('support.index')->with('ok', 'Arşiv durumu değişti.');
    }

    public function destroy(SupportRequest $supportRequest)
    {
        abort_unless($this->admin(), 403);
        $supportRequest->delete();

        return redirect()->route('support.index')->with('ok', 'Talep silindi.');
    }

    /** Herkese acik demo talep formu. */
    public function demoForm()
    {
        return view('support.demo');
    }

    public function demoStore(Request $request)
    {
        // Bal kabi alan: botlar doldurur, gercek kullanici gormez.
        if ($request->filled('website')) {
            return redirect()->route('demo.form')->with('ok', 'Talebiniz alındı.');
        }
        $d = $request->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:160'], 'message' => ['required', 'string', 'max:2000']]);
        $r = SupportRequest::create([
            'source' => 'demo', 'guest_name' => $d['name'], 'guest_email' => $d['email'], 'subject' => 'Demo talebi: '.$d['name'],
            'message' => $d['message'], 'category' => 'other',
        ]);
        $this->notify->send(null, 'Yeni demo talebi', $d['name'], route('support.show', $r, false), $this->adminIds());

        return redirect()->route('demo.form')->with('ok', 'Talebiniz alındı, size dönüş yapacağız.');
    }
}
