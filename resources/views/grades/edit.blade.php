@extends('layouts.app')
@section('title', 'Notu düzenle')
@section('content')
<x-page-head title="Notu düzenle" :sub="$grade->student->user->name" :back="[route('grades.index'), 'Notlar']" />
<form method="POST" action="{{ route('grades.update', $grade) }}" class="card form-card">@csrf @method('PUT')
<div class="fgrid">
<x-fg label="Sınav türü"><select name="exam_type" class="field">@foreach(config('school.exam_types') as $k => $t)<option value="{{ $k }}" @selected($grade->exam_type === $k)>{{ $t['label'] }}</option>@endforeach</select></x-fg>
<x-fg label="Ders"><select name="course_id" class="field"><option value="">Ders yok</option>@foreach($courses as $co)<option value="{{ $co->id }}" @selected($grade->course_id === $co->id)>{{ $co->name }}</option>@endforeach</select></x-fg>
<x-fg label="Başlık" full><input name="title" value="{{ old('title', $grade->title) }}" maxlength="120" placeholder="Başlık" class="field"></x-fg>
<x-fg label="Not (0–100)"><input name="score" type="number" step="0.01" min="0" max="100" value="{{ old('score', $grade->score) }}" class="field" required></x-fg>
<x-fg label="Sınav tarihi"><input type="date" name="exam_date" value="{{ old('exam_date', $grade->exam_date->format('Y-m-d')) }}" max="{{ today()->toDateString() }}" class="field" required></x-fg>
<x-fg label="Dönem" full><input name="term" value="{{ old('term', $grade->term) }}" class="field" required></x-fg></div>
<div class="form-actions"><button class="btn btn-primary">Kaydet</button><a class="btn btn-ghost" href="{{ route('grades.index') }}">Vazgeç</a></div></form>
@endsection
