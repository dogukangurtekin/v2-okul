@extends('layouts.app')
@section('title', 'Öğretmene Mesaj')
@section('content')
<x-s-head icon="💬" title="Mesajlarım" sub="Soruların için öğretmenine yaz." />
<form method="POST" action="{{ route('student.messages.send') }}" class="s-card" style="max-width:620px;margin-bottom:1.5rem;display:grid;gap:.8rem">@csrf
  <select name="to" class="field"><option value="teacher">👩‍🏫 Öğretmenim{{ $teacherUser ? ' ('.$teacherUser->name.')' : ' (atanmamış)' }}</option><option value="admin">🏫 Yönetici</option></select>
  <textarea name="body" rows="3" maxlength="500" placeholder="Mesajını yaz… (en fazla 500 karakter)" class="field" required></textarea>
  <div><button class="s-btn">Gönder ✉️</button></div>
</form>
<div style="max-width:620px;display:grid;gap:1rem" class="rise">
@foreach($messages as $m)
  <div>
    <div class="bubble me"><small>Sen · {{ $m->created_at->format('d.m H:i') }} · {{ $m->recipient_type === 'admin' ? 'Yönetici' : 'Öğretmen' }}</small><span style="white-space:pre-line">{{ $m->body }}</span></div>
    @if($m->reply_body)<div class="bubble" style="margin-top:.5rem;background:#ecfdf5;border-color:#a7f3d0"><small>{{ $m->replier?->name }} yanıtladı</small><span style="white-space:pre-line">{{ $m->reply_body }}</span></div>
    @else<div style="font-size:.78rem;color:#8b5cf6;font-weight:800;margin:.3rem 0 0 .5rem">⏳ Yanıt bekleniyor…</div>@endif
  </div>
@endforeach
@if($messages->isEmpty())<div class="s-card" style="text-align:center;padding:2rem"><div style="font-size:2.6rem">📭</div><h3>Henüz mesajın yok</h3></div>@endif
</div>
@endsection
