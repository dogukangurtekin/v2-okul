@extends('layouts.app')
@section('title', 'Bildirimler')
@section('content')
<x-page-head title="Bildirimler"><form method="POST" action="{{ route('notifications.readall') }}">@csrf<button class="btn btn-ghost">Hepsini okundu yap</button></form></x-page-head>
<div style="display:grid;gap:.6rem;max-width:760px;margin-bottom:1.4rem">
@forelse($items as $n)
<form method="POST" action="{{ route('notifications.read', $n) }}" class="card" style="{{ $n->pivot->read_at ? 'opacity:.65' : 'border-left:4px solid var(--brand)' }}">@csrf
<button style="text-align:left;width:100%;padding:.9rem 1.1rem;background:none;border:0;cursor:pointer"><div style="font-weight:700">{{ $n->title }}</div><div style="font-size:.88rem;color:#475569">{{ $n->body }}</div><div class="help" style="margin-top:.25rem">{{ $n->created_at->diffForHumans() }}</div></button></form>
@empty<x-empty icon="🔔" title="Bildirim yok" />@endforelse
</div>
{{ $items->links() }}
<div class="card card-pad" style="max-width:760px;margin-top:1.4rem">
  <b style="display:block;margin-bottom:.4rem">Anlık bildirimler (push)</b>
  @if($vapid)<button id="pushbtn" class="btn btn-primary btn-sm">Bu cihazda aç</button> <span id="pushmsg" class="help"></span>
  @else<p class="help" style="margin:0">Sunucuda push henüz yapılandırılmadı (README: VAPID).</p>@endif
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
