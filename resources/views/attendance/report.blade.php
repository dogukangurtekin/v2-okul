@extends('layouts.app')
@section('title', 'Devamsızlık raporu')
@section('content')
<x-page-head title="Devamsızlık Raporu" :back="[route('attendance.sheet'), 'Yoklama']" />
<form class="toolbar">
<select name="class" class="field" style="width:auto" required><option value="">Sınıf seçin</option>@foreach($classes as $c)<option value="{{ $c->id }}" @selected($class?->id === $c->id)>{{ $c->label }}</option>@endforeach</select>
<input type="date" name="from" value="{{ $from }}" class="field" style="width:auto"><input type="date" name="to" value="{{ $to }}" class="field" style="width:auto">
<button class="btn btn-primary">Göster</button>
@if($class)<a class="btn btn-ghost" href="{{ route('attendance.export', ['class' => $class->id, 'from' => $from, 'to' => $to]) }}">⬇ CSV</a>@endif</form>
@if($class)
<div class="card tbl-wrap" style="max-width:860px"><table class="tbl">
<thead><tr><th>Öğrenci</th><th>Geldi</th><th>Geç</th><th>İzinli</th><th>Gelmedi</th><th>Devam</th></tr></thead><tbody>
@foreach($students as $s)@php($a = $sum[$s->id])@php($low = ($a['rate'] ?? 100) < 85)
<tr style="{{ $low ? 'background:var(--tint-bad)' : '' }}"><td><div class="who"><x-initials :name="$s->user->name" :size="30" /><b>{{ $s->user->name }}</b></div></td><td>{{ $a['present'] }}</td><td>{{ $a['late'] }}</td><td>{{ $a['excused'] }}</td><td>{{ $a['absent'] }}</td><td><span class="badge {{ $low ? 'badge-bad' : 'badge-ok' }}">{{ $a['rate'] !== null ? '%'.$a['rate'] : '—' }}</span></td></tr>
@endforeach</tbody></table></div>
<p class="help" style="margin-top:.6rem">Devam oranı: (geldi + geç) / (kayıtlar − izinli). %85 altı kırmızı.</p>
@endif
@endsection
