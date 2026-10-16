@extends('layouts.app')
@section('title', 'Sınıf')
@section('content')
<x-page-head :title="$class->exists ? 'Sınıfı düzenle' : 'Yeni sınıf'" :back="[route('classes.index'), 'Sınıflar']" />
<form method="POST" action="{{ $class->exists ? route('classes.update', $class) : route('classes.store') }}" class="card form-card">
@csrf @if($class->exists) @method('PUT') @endif
<div class="fgrid">
  <x-fg label="Sınıf adı" for="name" help="Örn. 5 veya 5. Sınıf"><input id="name" class="field" name="name" value="{{ old('name', $class->name) }}" placeholder="5" required></x-fg>
  <x-fg label="Şube" for="section" help="Örn. A"><input id="section" class="field" name="section" value="{{ old('section', $class->section) }}" placeholder="A" required></x-fg>
  <x-fg label="Kademe" for="grade_level"><input id="grade_level" class="field" name="grade_level" type="number" min="1" max="12" value="{{ old('grade_level', $class->grade_level) }}" required></x-fg>
  <x-fg label="Eğitim yılı" for="academic_year"><input id="academic_year" class="field" name="academic_year" value="{{ old('academic_year', $class->academic_year) }}" required></x-fg>
  <x-fg label="Sınıf öğretmeni" for="teacher_id" full><select id="teacher_id" class="field" name="teacher_id"><option value="">Öğretmen yok</option>
    @foreach($teachers as $t)<option value="{{ $t->id }}" @selected(old('teacher_id', $class->teacher_id) == $t->id)>{{ $t->user->name }}</option>@endforeach</select></x-fg>
</div>
<div class="form-actions"><button class="btn btn-primary">Kaydet</button><a class="btn btn-ghost" href="{{ route('classes.index') }}">Vazgeç</a></div>
</form>
@endsection
