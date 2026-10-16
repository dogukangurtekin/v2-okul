@extends('layouts.app')
@section('title', 'Duyuru')
@section('content')
<h1 class="text-2xl font-bold mb-4">{{ $a->exists ? 'Duyuruyu düzenle' : 'Yeni duyuru' }}</h1>
<form method="POST" action="{{ $a->exists ? route('announcements.update', $a) : route('announcements.store') }}" class="bg-white p-6 rounded-xl shadow max-w-xl space-y-3">@csrf @if($a->exists) @method('PUT') @endif
<input name="title" value="{{ old('title', $a->title) }}" placeholder="Başlık" class="w-full border rounded px-3 py-2" required>
<textarea name="content" rows="5" class="w-full border rounded px-3 py-2" required>{{ old('content', $a->content) }}</textarea>
<select name="audience" class="w-full border rounded px-3 py-2">@foreach(\App\Models\Announcement::AUDIENCES as $k => $v)<option value="{{ $k }}" @selected(old('audience', $a->audience) === $k)>{{ $v }}</option>@endforeach</select>
<input type="datetime-local" name="published_at" value="{{ old('published_at', $a->published_at?->format('Y-m-d\TH:i')) }}" class="w-full border rounded px-3 py-2">
@unless($a->exists)<label class="flex gap-2 items-center text-sm"><input type="checkbox" name="notify" value="1" checked> Hedef kitleye bildirim de gönder</label>@endunless
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Kaydet</button></form>
@endsection
