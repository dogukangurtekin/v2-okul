@extends('layouts.app')
@section('title', 'Ödev')
@section('content')
<h1 class="text-2xl font-bold mb-4">{{ $assignment->exists ? 'Ödevi düzenle' : 'Ödev ver' }}</h1>
<form method="POST" action="{{ $assignment->exists ? route('assignments.update', $assignment) : route('assignments.store') }}" class="bg-white p-6 rounded-xl shadow max-w-2xl space-y-3">
@csrf @if($assignment->exists) @method('PUT') @endif
<select name="type" id="type" class="w-full border rounded px-3 py-2"><option value="course" @selected(old('type', $assignment->type) === 'course')>Ders ödevi</option><option value="game" @selected(old('type', $assignment->type) === 'game')>Oyun / etkinlik ödevi</option></select>
<select name="course_id" class="w-full border rounded px-3 py-2"><option value="">Ders seçin</option>@foreach($courses as $c)<option value="{{ $c->id }}" @selected(old('course_id', $assignment->course_id) == $c->id)>{{ $c->name }}</option>@endforeach</select>
<select name="game_slug" class="w-full border rounded px-3 py-2"><option value="">Oyun seçin</option>@foreach($games as $slug => $name)<option value="{{ $slug }}" @selected(old('game_slug', $assignment->game_slug) === $slug)>{{ $name }}</option>@endforeach</select>
<input name="title" value="{{ old('title', $assignment->title) }}" placeholder="Başlık" class="w-full border rounded px-3 py-2" required>
<textarea name="details" rows="3" placeholder="Açıklama" class="w-full border rounded px-3 py-2">{{ old('details', $assignment->details) }}</textarea>
<div class="grid grid-cols-4 gap-2">
<input name="due_date" type="date" value="{{ old('due_date', $assignment->due_date?->format('Y-m-d')) }}" class="border rounded px-3 py-2">
<input name="level_from" type="number" min="1" value="{{ old('level_from', $assignment->level_from) }}" placeholder="Seviye ↓" class="border rounded px-3 py-2">
<input name="level_to" type="number" min="1" value="{{ old('level_to', $assignment->level_to) }}" placeholder="Seviye ↑" class="border rounded px-3 py-2">
<input name="xp_reward" type="number" min="0" value="{{ old('xp_reward', $assignment->xp_reward) }}" placeholder="XP" class="border rounded px-3 py-2" required></div>
<div class="font-semibold">Sınıflar</div>
<div class="flex flex-wrap gap-3">@foreach($classes as $c)<label class="flex gap-1 items-center"><input type="checkbox" name="classes[]" value="{{ $c->id }}" @checked(in_array($c->id, old('classes', $assignment->classes->pluck('id')->all())))> {{ $c->label }}</label>@endforeach</div>
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Kaydet</button>
</form>
@endsection
