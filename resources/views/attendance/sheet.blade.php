@extends('layouts.app')
@section('title', 'Yoklama')
@section('content')
<x-page-head title="Yoklama" sub="Sınıf ve tarih seçip yoklamayı alın"><a class="btn btn-ghost" href="{{ route('attendance.report') }}">Devamsızlık raporu →</a></x-page-head>
<form class="toolbar">
<select name="class" class="field" style="width:auto" required><option value="">Sınıf seçin</option>@foreach($classes as $c)<option value="{{ $c->id }}" @selected($class?->id === $c->id)>{{ $c->label }}</option>@endforeach</select>
<input type="date" name="date" value="{{ $date }}" max="{{ today()->toDateString() }}" class="field" style="width:auto">
@if($courses->isNotEmpty())<select name="course" class="field" style="width:auto"><option value="">Genel (günlük)</option>@foreach($courses as $co)<option value="{{ $co->id }}" @selected($courseId === $co->id)>{{ $co->name }}</option>@endforeach</select>@endif
<button class="btn btn-primary">Getir</button></form>
@if($class)
<form method="POST" action="{{ route('attendance.save') }}" style="max-width:980px">@csrf
<input type="hidden" name="school_class_id" value="{{ $class->id }}"><input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="course_id" value="{{ $courseId }}">
<div class="page-actions" style="margin-bottom:.7rem">@foreach(config('school.attendance_statuses') as $k => $v)<button type="button" data-all="{{ $k }}" class="btn btn-soft btn-sm">Hepsi: {{ $v }}</button>@endforeach</div>
<div class="card tbl-wrap"><table class="tbl"><tbody>
@forelse($students as $s)@php($cur = $existing[$s->id]->status ?? 'present')
<tr><td><div class="who"><x-initials :name="$s->user->name" :size="32" /><div><b>{{ $s->user->name }}</b><small>{{ $s->student_no }}</small></div></div></td>
<td style="white-space:nowrap"><div class="chip-pick" style="gap:.3rem">@foreach(config('school.attendance_statuses') as $k => $v)<label><input type="radio" name="rows[{{ $s->id }}][status]" value="{{ $k }}" @checked($cur === $k)> {{ $v }}</label>@endforeach</div></td>
<td><input name="rows[{{ $s->id }}][note]" value="{{ $existing[$s->id]->note ?? '' }}" maxlength="200" placeholder="Not" class="field"></td></tr>
@empty<tr><td><x-empty icon="👥" title="Bu sınıfta öğrenci yok" /></td></tr>@endforelse
</tbody></table></div>
@if($students->isNotEmpty())<div class="form-actions"><button class="btn btn-primary">Kaydet @if($existing->isNotEmpty())<span style="opacity:.75;font-size:.75rem">(güncelle)</span>@endif</button></div>@endif
</form>
<script>document.querySelectorAll('[data-all]').forEach(b => b.onclick = () => document.querySelectorAll('input[type=radio][value="'+b.dataset.all+'"]').forEach(r => r.checked = true));</script>
@endif
@endsection
