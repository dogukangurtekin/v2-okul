@extends('layouts.app')
@section('title', 'Öğrenci Mesajları')
@section('content')
<div class="flex justify-between mb-4"><h1 class="text-2xl font-bold">Öğrenci Mesajları</h1>
@if(auth()->user()->hasRole('admin'))<form method="POST" action="{{ route('messages.clear') }}" onsubmit="return confirm('Yanıtlananlar gizlensin mi?')">@csrf @method('DELETE')<button class="border rounded px-3 py-1 bg-white">Yanıtlananları temizle</button></form>@endif</div>
<div class="space-y-3 max-w-2xl">
@forelse($messages as $m)
<div class="bg-white rounded-xl shadow p-4 {{ $m->replied_at ? 'opacity-70' : 'border-l-4 border-indigo-500' }}">
<div class="text-sm text-slate-500">{{ $m->student->user->name }} · {{ $m->student->schoolClass?->label }} · {{ $m->created_at->format('d.m H:i') }}</div>
<div class="whitespace-pre-line my-1">{{ $m->body }}</div>
@if($m->reply_body)<div class="p-2 bg-indigo-50 rounded text-sm whitespace-pre-line">{{ $m->reply_body }}</div>
@else<form method="POST" action="{{ route('messages.reply', $m) }}" class="flex gap-2 mt-2">@csrf<input name="reply" maxlength="500" placeholder="Yanıt" class="flex-1 border rounded px-3 py-1" required><button class="bg-indigo-600 text-white px-3 rounded">Yanıtla</button></form>@endif
<form method="POST" action="{{ route('messages.destroy', $m) }}" class="mt-1">@csrf @method('DELETE')<button class="text-xs text-red-600">Gizle</button></form></div>
@empty<p class="text-slate-500">Mesaj yok.</p>@endforelse</div>
<div class="mt-4">{{ $messages->links() }}</div>
@endsection
