@extends('layouts.app')
@section('title', 'Sınıf atama')
@section('content')
<h1 class="text-2xl font-bold mb-1">{{ $user->name }} — sınıf atama</h1>
<p class="text-slate-500 mb-4">İşaretli sınıfların öğretmeni bu kişi olur. Başka öğretmene ait bir sınıfı işaretlerseniz devralır.</p>
<form method="POST" action="{{ route('users.classes.save', $user) }}" class="bg-white p-5 rounded-xl shadow max-w-2xl mb-6">@csrf
<div class="grid grid-cols-2 gap-2">@foreach($classes as $c)
<label class="flex gap-2 items-center border rounded p-2 {{ $c->teacher_id && $c->teacher_id !== $teacher->id ? 'bg-amber-50' : '' }}">
<input type="checkbox" name="classes[]" value="{{ $c->id }}" @checked($c->teacher_id === $teacher->id)> {{ $c->label }} <span class="text-xs text-slate-400">{{ $c->students_count }} öğrenci{{ $c->teacher_id && $c->teacher_id !== $teacher->id ? ' · şu an: '.$c->teacher?->user?->name : '' }}</span></label>@endforeach</div>
<button class="mt-4 bg-indigo-600 text-white px-4 py-2 rounded">Kaydet</button></form>
<form method="POST" action="{{ route('users.classes.grade', $user) }}" class="bg-white p-5 rounded-xl shadow max-w-2xl flex gap-2 items-center">@csrf
<span>Kademeye göre hepsini ata:</span><input type="number" name="grade_level" min="1" max="12" class="border rounded px-3 py-2 w-24" required><button class="bg-slate-700 text-white px-4 py-2 rounded">Ata</button></form>
@endsection
