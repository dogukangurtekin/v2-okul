@extends('layouts.app')
@section('title', 'Sınıf')
@section('content')
<h1 class="text-2xl font-bold mb-4">{{ $class->exists ? 'Sınıfı düzenle' : 'Yeni sınıf' }}</h1>
<form method="POST" action="{{ $class->exists ? route('classes.update', $class) : route('classes.store') }}" class="bg-white p-6 rounded-xl shadow max-w-lg space-y-3">
@csrf @if($class->exists) @method('PUT') @endif
<input name="name" value="{{ old('name', $class->name) }}" placeholder="Ad (örn. 5)" class="w-full border rounded px-3 py-2" required>
<input name="section" value="{{ old('section', $class->section) }}" placeholder="Şube (örn. A)" class="w-full border rounded px-3 py-2" required>
<input name="grade_level" type="number" min="1" max="12" value="{{ old('grade_level', $class->grade_level) }}" placeholder="Kademe" class="w-full border rounded px-3 py-2" required>
<input name="academic_year" value="{{ old('academic_year', $class->academic_year) }}" class="w-full border rounded px-3 py-2" required>
<select name="teacher_id" class="w-full border rounded px-3 py-2"><option value="">Öğretmen yok</option>
@foreach($teachers as $t)<option value="{{ $t->id }}" @selected(old('teacher_id', $class->teacher_id) == $t->id)>{{ $t->user->name }}</option>@endforeach</select>
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Kaydet</button>
</form>
@endsection
