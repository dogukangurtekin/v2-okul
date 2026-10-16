@extends('layouts.app')
@section('title', 'Öğretmene Mesaj')
@section('content')
<h1 class="text-2xl font-bold mb-4">Mesajlarım</h1>
<form method="POST" action="{{ route('student.messages.send') }}" class="bg-white p-5 rounded-xl shadow max-w-xl space-y-3 mb-6">@csrf
<select name="to" class="w-full border rounded px-3 py-2"><option value="teacher">Öğretmenim{{ $teacherUser ? ' ('.$teacherUser->name.')' : ' (atanmamış)' }}</option><option value="admin">Yönetici</option></select>
<textarea name="body" rows="3" maxlength="500" placeholder="Mesajın (en fazla 500 karakter)" class="w-full border rounded px-3 py-2" required></textarea>
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Gönder</button></form>
<div class="space-y-3 max-w-xl">
@foreach($messages as $m)
<div class="bg-white rounded-xl shadow p-4"><div class="text-xs text-slate-400">{{ $m->created_at->format('d.m H:i') }} · {{ $m->recipient_type === 'admin' ? 'Yönetici' : 'Öğretmen' }}</div>
<div class="whitespace-pre-line">{{ $m->body }}</div>
@if($m->reply_body)<div class="mt-2 p-3 bg-indigo-50 rounded whitespace-pre-line"><div class="text-xs text-slate-500">{{ $m->replier?->name }} yanıtladı</div>{{ $m->reply_body }}</div>@else<div class="text-xs text-slate-400 mt-1">Yanıt bekleniyor…</div>@endif</div>
@endforeach</div>
@endsection
