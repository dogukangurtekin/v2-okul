@extends('layouts.app')
@section('title', 'Not girişi')
@section('content')
<h1 class="text-2xl font-bold mb-4">Not Girişi</h1>
<form class="flex gap-2 mb-4"><select name="class" class="border rounded px-3 py-2 bg-white" onchange="this.form.submit()" required><option value="">Sınıf seçin</option>@foreach($classes as $c)<option value="{{ $c->id }}" @selected($class?->id === $c->id)>{{ $c->label }}</option>@endforeach</select></form>
@if($class)
<form method="POST" action="{{ route('grades.store') }}" class="max-w-3xl space-y-3">@csrf
<input type="hidden" name="school_class_id" value="{{ $class->id }}">
<div class="bg-white p-4 rounded-xl shadow grid grid-cols-2 gap-2">
<select name="exam_type" class="border rounded px-3 py-2">@foreach(config('school.exam_types') as $k => $t)<option value="{{ $k }}">{{ $t['label'] }} (ağırlık {{ $t['weight'] }})</option>@endforeach</select>
<select name="course_id" class="border rounded px-3 py-2"><option value="">Ders (isteğe bağlı)</option>@foreach($courses as $co)<option value="{{ $co->id }}">{{ $co->name }}</option>@endforeach</select>
<input name="title" maxlength="120" placeholder="Başlık (örn. 1. Yazılı)" class="border rounded px-3 py-2">
<input type="date" name="exam_date" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" class="border rounded px-3 py-2" required>
<input name="term" value="{{ $term }}" class="border rounded px-3 py-2 col-span-2" required></div>
<table class="w-full bg-white rounded-xl shadow text-sm">
@foreach($students as $s)<tr class="border-b"><td class="p-2">{{ $s->user->name }} <span class="text-slate-400">{{ $s->student_no }}</span></td><td class="p-2 w-32"><input name="scores[{{ $s->id }}]" inputmode="decimal" placeholder="0–100" class="border rounded px-2 py-1 w-full"></td></tr>@endforeach</table>
<p class="text-xs text-slate-500">Boş bırakılan öğrenciye not eklenmez. Virgül veya nokta kullanabilirsiniz.</p>
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Kaydet</button></form>
@endif
@endsection
