@extends('layouts.app')
@section('title', 'Yoklama')
@section('content')
<div class="flex justify-between mb-4"><h1 class="text-2xl font-bold">Yoklama</h1><a class="text-indigo-600" href="{{ route('attendance.report') }}">Devamsızlık raporu →</a></div>
<form class="flex flex-wrap gap-2 mb-4">
<select name="class" class="border rounded px-3 py-2 bg-white" required><option value="">Sınıf seçin</option>@foreach($classes as $c)<option value="{{ $c->id }}" @selected($class?->id === $c->id)>{{ $c->label }}</option>@endforeach</select>
<input type="date" name="date" value="{{ $date }}" max="{{ today()->toDateString() }}" class="border rounded px-3 py-2">
@if($courses->isNotEmpty())<select name="course" class="border rounded px-3 py-2 bg-white"><option value="">Genel (günlük)</option>@foreach($courses as $co)<option value="{{ $co->id }}" @selected($courseId === $co->id)>{{ $co->name }}</option>@endforeach</select>@endif
<button class="bg-slate-700 text-white px-4 rounded">Getir</button></form>
@if($class)
<form method="POST" action="{{ route('attendance.save') }}" class="max-w-3xl">@csrf
<input type="hidden" name="school_class_id" value="{{ $class->id }}"><input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="course_id" value="{{ $courseId }}">
<div class="flex gap-2 mb-2 text-sm">@foreach(config('school.attendance_statuses') as $k => $v)<button type="button" data-all="{{ $k }}" class="border rounded px-2 py-1 bg-white">Hepsi: {{ $v }}</button>@endforeach</div>
<table class="w-full bg-white rounded-xl shadow text-sm">
@forelse($students as $s)@php($cur = $existing[$s->id]->status ?? 'present')
<tr class="border-b"><td class="p-2">{{ $s->user->name }} <span class="text-slate-400">{{ $s->student_no }}</span></td>
<td class="p-2 space-x-2 whitespace-nowrap">@foreach(config('school.attendance_statuses') as $k => $v)<label><input type="radio" name="rows[{{ $s->id }}][status]" value="{{ $k }}" @checked($cur === $k)> {{ $v }}</label>@endforeach</td>
<td class="p-2"><input name="rows[{{ $s->id }}][note]" value="{{ $existing[$s->id]->note ?? '' }}" maxlength="200" placeholder="Not" class="border rounded px-2 py-1 w-full"></td></tr>
@empty<tr><td class="p-3 text-slate-500">Bu sınıfta öğrenci yok.</td></tr>@endforelse
</table>
@if($students->isNotEmpty())<button class="mt-3 bg-indigo-600 text-white px-4 py-2 rounded">Kaydet @if($existing->isNotEmpty())<span class="text-xs opacity-75">(güncelle)</span>@endif</button>@endif
</form>
<script>document.querySelectorAll('[data-all]').forEach(b => b.onclick = () => document.querySelectorAll('input[type=radio][value="'+b.dataset.all+'"]').forEach(r => r.checked = true));</script>
@endif
@endsection
