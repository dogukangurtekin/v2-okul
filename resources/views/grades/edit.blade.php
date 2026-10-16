@extends('layouts.app')
@section('title', 'Notu düzenle')
@section('content')
<h1 class="text-2xl font-bold mb-1">Notu düzenle</h1><p class="text-slate-500 mb-4">{{ $grade->student->user->name }}</p>
<form method="POST" action="{{ route('grades.update', $grade) }}" class="bg-white p-5 rounded-xl shadow max-w-lg space-y-3">@csrf @method('PUT')
<select name="exam_type" class="w-full border rounded px-3 py-2">@foreach(config('school.exam_types') as $k => $t)<option value="{{ $k }}" @selected($grade->exam_type === $k)>{{ $t['label'] }}</option>@endforeach</select>
<select name="course_id" class="w-full border rounded px-3 py-2"><option value="">Ders yok</option>@foreach($courses as $co)<option value="{{ $co->id }}" @selected($grade->course_id === $co->id)>{{ $co->name }}</option>@endforeach</select>
<input name="title" value="{{ old('title', $grade->title) }}" maxlength="120" placeholder="Başlık" class="w-full border rounded px-3 py-2">
<input name="score" type="number" step="0.01" min="0" max="100" value="{{ old('score', $grade->score) }}" class="w-full border rounded px-3 py-2" required>
<input type="date" name="exam_date" value="{{ old('exam_date', $grade->exam_date->format('Y-m-d')) }}" max="{{ today()->toDateString() }}" class="w-full border rounded px-3 py-2" required>
<input name="term" value="{{ old('term', $grade->term) }}" class="w-full border rounded px-3 py-2" required>
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Kaydet</button></form>
@endsection
