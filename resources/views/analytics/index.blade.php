@extends('layouts.app')
@section('title', 'Analitik')
@section('content')
<div class="flex justify-between mb-4"><h1 class="text-2xl font-bold">Öğrenci Analitiği</h1>
<form class="flex gap-2"><select name="class" class="border rounded px-3 py-2 bg-white" onchange="this.form.submit()"><option value="">Tüm sınıflar</option>@foreach($classes as $c)<option value="{{ $c->id }}" @selected(request('class') == $c->id)>{{ $c->label }}</option>@endforeach</select>
<a class="bg-slate-700 text-white px-3 py-2 rounded" href="{{ route('analytics.export', request()->only('class')) }}">CSV indir</a>
<a class="border px-3 py-2 rounded bg-white" href="{{ route('reports.parentlist', request()->only('class')) }}">Veli listesi</a></form></div>
<div class="grid grid-cols-3 gap-4 max-w-2xl mb-4">
<div class="bg-white rounded-xl shadow p-4"><div class="text-slate-500 text-sm">Öğrenci</div><div class="text-2xl font-bold">{{ $students->count() }}</div></div>
<div class="bg-white rounded-xl shadow p-4"><div class="text-slate-500 text-sm">Ortalama başarı</div><div class="text-2xl font-bold">{{ $avg !== null ? '%'.$avg : '—' }}</div></div>
<div class="bg-white rounded-xl shadow p-4"><div class="text-slate-500 text-sm">Destek gerekebilir (&lt;%40)</div><div class="text-2xl font-bold text-red-600">{{ $atRisk }}</div></div></div>
<table class="w-full bg-white rounded-xl shadow text-sm">
<tr class="text-left border-b"><th class="p-3">Öğrenci</th><th>Sınıf</th><th>Başarı</th><th>XP</th><th>Ödev</th><th>Ders</th><th>Oyun sev.</th><th>KDK</th><th>Devam</th><th>Not ort.</th><th>Dk</th><th>Son görülme</th></tr>
@foreach($students as $s)@php($a = $stats[$s->id])
<tr class="border-b"><td class="p-3"><a class="text-indigo-600" href="{{ route('reports.show', $s) }}">{{ $s->user->name }}</a></td><td>{{ $s->schoolClass?->label }}</td>
<td class="{{ ($a['score'] ?? 100) < 40 ? 'text-red-600 font-semibold' : '' }}">{{ $a['score'] !== null ? '%'.$a['score'] : '—' }}</td><td>{{ $a['xp_total'] }}</td>
<td>{{ $a['assignments_done'] }}/{{ $a['assignments_total'] }}</td><td>{{ $a['courses_done'] }}/{{ $a['courses_total'] }}</td><td>{{ $a['game_levels'] }}</td><td>{{ $a['best_wpm'] ?: '—' }}</td><td>{{ $a['attendance_rate'] !== null ? '%'.$a['attendance_rate'] : '—' }}</td><td>{{ $a['grade_avg'] ?? '—' }}</td><td>{{ $a['minutes'] }}</td><td class="text-slate-400">{{ $a['last_seen']?->diffForHumans() ?? '—' }}</td></tr>
@endforeach
</table>
@endsection
