@extends('layouts.app')
@section('title', $r->subject)
@section('content')
<div class="max-w-2xl">
<h1 class="text-2xl font-bold">{{ $r->subject }}</h1>
<p class="text-slate-500 mb-4">{{ $r->sender_name }}@if($r->guest_email) · {{ $r->guest_email }}@endif · {{ \App\Models\SupportRequest::STATUSES[$r->status] }} · öncelik: {{ $r->priority }}</p>
<div class="bg-white rounded-xl shadow p-4 whitespace-pre-line mb-4">{{ $r->message }}</div>
@foreach($replies as $p)
<div class="rounded-xl p-4 mb-2 whitespace-pre-line {{ $p->internal_note ? 'bg-amber-50 border border-amber-200' : ($p->sender_user_id === $r->sender_user_id ? 'bg-white shadow' : 'bg-indigo-50') }}">
<div class="text-xs text-slate-500 mb-1">{{ $p->sender->name }} · {{ $p->created_at->format('d.m H:i') }}@if($p->internal_note) · <b>dahili not</b>@endif</div>{{ $p->message }}</div>
@endforeach
@if($r->status !== 'closed' && $r->sender_user_id)
<form method="POST" action="{{ route('support.reply', $r) }}" class="mt-4 space-y-2">@csrf
<textarea name="message" rows="3" class="w-full border rounded px-3 py-2" placeholder="Yanıtınız" required maxlength="3000"></textarea>
@if(auth()->user()->hasRole('admin'))<label class="text-sm flex gap-1 items-center"><input type="checkbox" name="internal_note" value="1"> Dahili not (gönderen görmez)</label>@endif
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Yanıtla</button></form>
@endif
<div class="mt-4 flex gap-3 text-sm">
@if($r->status !== 'closed')<form method="POST" action="{{ route('support.close', $r) }}">@csrf<button class="border rounded px-3 py-1 bg-white">Kapat</button></form>@endif
@if(auth()->user()->hasRole('admin'))
<form method="POST" action="{{ route('support.archive', $r) }}">@csrf<button class="border rounded px-3 py-1 bg-white">{{ $r->archived_at ? 'Arşivden çıkar' : 'Arşivle' }}</button></form>
<form method="POST" action="{{ route('support.destroy', $r) }}" onsubmit="return confirm('Kalıcı silinsin mi?')">@csrf @method('DELETE')<button class="text-red-600 px-3 py-1">Sil</button></form>@endif
</div></div>
@endsection
