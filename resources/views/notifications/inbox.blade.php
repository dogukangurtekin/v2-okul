@extends('layouts.app')
@section('title', 'Bildirimler')
@section('content')
<div class="flex justify-between mb-4"><h1 class="text-2xl font-bold">Bildirimler</h1>
<form method="POST" action="{{ route('notifications.readall') }}">@csrf<button class="border rounded px-3 py-1 bg-white">Hepsini okundu yap</button></form></div>
<div class="space-y-2 max-w-2xl mb-6">
@forelse($items as $n)
<form method="POST" action="{{ route('notifications.read', $n) }}" class="bg-white rounded-xl shadow p-4 {{ $n->pivot->read_at ? 'opacity-60' : 'border-l-4 border-indigo-500' }}">@csrf
<button class="text-left w-full"><div class="font-semibold">{{ $n->title }}</div><div class="text-sm text-slate-600">{{ $n->body }}</div><div class="text-xs text-slate-400 mt-1">{{ $n->created_at->diffForHumans() }}</div></button></form>
@empty<p class="text-slate-500">Bildirim yok.</p>@endforelse
</div>
{{ $items->links() }}
<div class="bg-white rounded-xl shadow p-4 max-w-2xl mt-6">
  <div class="font-semibold mb-1">Anlık bildirimler (push)</div>
  @if($vapid)<button id="pushbtn" class="bg-indigo-600 text-white px-3 py-1 rounded">Bu cihazda aç</button> <span id="pushmsg" class="text-sm text-slate-500"></span>
  @else<p class="text-sm text-slate-500">Sunucuda push henüz yapılandırılmadı (README: VAPID).</p>@endif
</div>
@if($vapid)
<script>
const b64 = s => Uint8Array.from(atob(s.replace(/-/g,'+').replace(/_/g,'/').padEnd(Math.ceil(s.length/4)*4,'=')), c => c.charCodeAt(0));
document.getElementById('pushbtn').onclick = async () => {
  const msg = document.getElementById('pushmsg');
  try {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) throw new Error('Tarayıcı desteklemiyor');
    if (await Notification.requestPermission() !== 'granted') throw new Error('İzin verilmedi');
    const reg = await navigator.serviceWorker.ready;
    const sub = await reg.pushManager.subscribe({userVisibleOnly:true, applicationServerKey:b64(@json($vapid))});
    await fetch(@json(route('push.subscribe')), {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':@json(csrf_token()),Accept:'application/json'}, body:JSON.stringify(sub.toJSON())});
    msg.textContent = '✅ Açıldı';
  } catch (e) { msg.textContent = e.message; }
};
</script>
@endif
@endsection
