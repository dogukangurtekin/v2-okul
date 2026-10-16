@extends('layouts.app')
@section('title', 'Bildirim Gönder')
@section('content')
<h1 class="text-2xl font-bold mb-4">Bildirim Gönder</h1>
<form method="POST" action="{{ route('notifications.send') }}" class="bg-white p-5 rounded-xl shadow max-w-xl space-y-3">@csrf
<input name="title" value="{{ old('title') }}" placeholder="Başlık" class="w-full border rounded px-3 py-2" required maxlength="120">
<textarea name="body" rows="3" placeholder="Mesaj" class="w-full border rounded px-3 py-2" required maxlength="500">{{ old('body') }}</textarea>
<div class="flex flex-wrap gap-3">@foreach($classes as $c)<label class="flex gap-1 items-center"><input type="checkbox" name="class_ids[]" value="{{ $c->id }}"> {{ $c->label }}</label>@endforeach</div>
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Gönder</button></form>
<h2 class="font-bold mt-8 mb-2">Gönderilenler</h2>
<table class="w-full max-w-xl bg-white rounded-xl shadow text-sm">@foreach($sent as $n)<tr class="border-b"><td class="p-2">{{ $n->title }}</td><td>{{ $n->recipients_count }} kişi</td><td class="text-slate-400">{{ $n->created_at->diffForHumans() }}</td></tr>@endforeach</table>
@endsection
