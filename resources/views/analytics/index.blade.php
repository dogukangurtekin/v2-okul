@extends('layouts.app')
@section('title', 'Analitik')
@section('content')
<x-page-head title="Öğrenci Analitiği" sub="Başarı, katılım ve ilerleme göstergeleri">
<form style="display:flex;gap:.5rem;flex-wrap:wrap"><select name="class" class="field" style="width:auto" onchange="this.form.submit()"><option value="">Tüm sınıflar</option>@foreach($classes as $c)<option value="{{ $c->id }}" @selected(request('class') == $c->id)>{{ $c->label }}</option>@endforeach</select>
<a class="btn btn-ghost" href="{{ route('analytics.export', request()->only('class')) }}">⬇ CSV indir</a>
<a class="btn btn-ghost" href="{{ route('reports.parentlist', request()->only('class')) }}">Veli listesi</a></form></x-page-head>
<div class="kpi" style="max-width:760px">
<div class="card"><b>{{ $students->count() }}</b><span>Öğrenci</span></div>
<div class="card"><b>{{ $avg !== null ? '%'.$avg : '—' }}</b><span>Ortalama başarı</span></div>
<div class="card"><b style="color:var(--bad)">{{ $atRisk }}</b><span>Destek gerekebilir (&lt;%40)</span></div></div>
<div class="card tbl-wrap"><table class="tbl">
<thead><tr><th>Öğrenci</th><th>Sınıf</th><th>Başarı</th><th>XP</th><th>Ödev</th><th>Ders</th><th>Oyun sev.</th><th>KDK</th><th>Devam</th><th>Not ort.</th><th>Dk</th><th>Son görülme</th></tr></thead><tbody>
@foreach($students as $s)@php($a = $stats[$s->id])@php($sc = $a['score'])
<tr><td><div class="who"><x-initials :name="$s->user->name" :size="30" /><a href="{{ route('reports.show', $s) }}" style="font-weight:700;color:var(--ink);text-decoration:none">{{ $s->user->name }}</a></div></td><td>{{ $s->schoolClass?->label }}</td>
<td style="min-width:110px">@if($sc !== null)<div style="display:flex;align-items:center;gap:.5rem"><b style="color:{{ $sc < 40 ? 'var(--bad)' : 'inherit' }}">%{{ $sc }}</b><div class="progress" style="flex:1"><i data-pct="{{ $sc }}" style="width:0"></i></div></div>@else — @endif</td><td>{{ $a['xp_total'] }}</td>
<td>{{ $a['assignments_done'] }}/{{ $a['assignments_total'] }}</td><td>{{ $a['courses_done'] }}/{{ $a['courses_total'] }}</td><td>{{ $a['game_levels'] }}</td><td>{{ $a['best_wpm'] ?: '—' }}</td><td>{{ $a['attendance_rate'] !== null ? '%'.$a['attendance_rate'] : '—' }}</td><td>{{ $a['grade_avg'] ?? '—' }}</td><td>{{ $a['minutes'] }}</td><td style="color:#94a3b8">{{ $a['last_seen']?->diffForHumans() ?? '—' }}</td></tr>
@endforeach
</tbody></table></div>
@endsection
