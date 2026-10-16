<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\StaffScope;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Gelen kutusu (herkes). */
    public function inbox(Request $request)
    {
        $items = $request->user()->notifications()->latest('app_notifications.created_at')->paginate(20);

        return view('notifications.inbox', ['items' => $items, 'vapid' => config('webpush.public_key')]);
    }

    public function read(Request $request, AppNotification $notification)
    {
        abort_unless($request->user()->notifications()->whereKey($notification->id)->exists(), 404);
        $request->user()->notifications()->updateExistingPivot($notification->id, ['read_at' => now()]);

        return $notification->url && str_starts_with($notification->url, '/') ? redirect($notification->url) : back();
    }

    public function readAll(Request $request)
    {
        $request->user()->notifications()->wherePivotNull('read_at')->each(fn ($n) => $request->user()->notifications()->updateExistingPivot($n->id, ['read_at' => now()]));

        return back();
    }

    /** Personel: gonderim formu + gecmis. */
    public function compose(Request $request)
    {
        return view('notifications.compose', [
            'classes' => StaffScope::classes($request->user())->get(),
            'sent' => AppNotification::where('sender_user_id', $request->user()->id)->withCount('recipients')->latest()->limit(15)->get(),
        ]);
    }

    public function send(Request $request, NotificationService $svc)
    {
        $d = $request->validate([
            'title' => ['required', 'string', 'max:120'], 'body' => ['required', 'string', 'max:500'],
            'class_ids' => ['required', 'array', 'min:1'], 'class_ids.*' => ['integer'],
        ]);
        $userIds = StaffScope::students($request->user())->whereIn('school_class_id', $d['class_ids'])->pluck('user_id');
        abort_if($userIds->isEmpty(), 422, 'Seçilen sınıflarda öğrenci yok.');

        $svc->send($request->user(), $d['title'], $d['body'], '/bildirimlerim', $userIds);

        return back()->with('ok', $userIds->count().' kişiye bildirim gönderildi.');
    }

    public function subscribe(Request $request)
    {
        $d = $request->validate(['endpoint' => ['required', 'url', 'max:512'], 'keys.p256dh' => ['required', 'string'], 'keys.auth' => ['required', 'string']]);
        PushSubscription::updateOrCreate(['endpoint' => $d['endpoint']], [
            'user_id' => $request->user()->id, 'p256dh' => $d['keys']['p256dh'], 'auth' => $d['keys']['auth'], 'user_agent' => substr((string) $request->userAgent(), 0, 250),
        ]);

        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $request)
    {
        PushSubscription::where('user_id', $request->user()->id)->where('endpoint', $request->input('endpoint'))->delete();

        return response()->json(['ok' => true]);
    }
}
