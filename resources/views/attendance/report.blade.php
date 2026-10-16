@extends('layouts.app')
@section('title', 'Devamsızlık raporu')
@section('content')
<h1 class="text-2xl font-bold mb-4">Devamsızlık Raporu</h1>
<form class="flex flex-wrap gap-2 mb-4">
<select name="class" class="border rounded px-3 py-2 bg-white" required><option value="">Sınıf seçin</option>@foreach($classes as $c)<option value="{{ $c->id }}" @selected($class?->id === $c->id)>{{ $c->label }}</option>@endforeach</select>
<input type="date" name="from" value="{{ $from }}" class="border rounded px-3 py-2"><input type="date" name="to" value="{{ $to }}" class="border rounded px-3 py-2">
<button class="bg-slate-700 text-white px-4 rounded">Göster</button>
@if($class)<a class="border rounded px-3 py-2 bg-white" href="{{ route('attendance.export', ['class' => $class->id, 'from' => $from, 'to' => $to]) }}">CSV</a>@endif</form>
@if($class)
<table class="w-full max-w-3xl bg-white rounded-xl shadow text-sm">
<tr class="text-left border-b"><th class="p-3">Öğrenci</th><th>Geldi</th><th>Geç</th><th>İzinli</th><th>Gelmedi</th><th>Devam</th></tr>
@foreach($students as $s)@php($a = $sum[$s->id])
<tr class="border-b {{ ($a['rate'] ?? 100) < 85 ? 'bg-red-50' : '' }}"><td class="p-3">{{ $s->user->name }}</td><td>{{ $a['present'] }}</td><td>{{ $a['late'] }}</td><td>{{ $a['excused'] }}</td><td>{{ $a['absent'] }}</td><td class="font-semibold">{{ $a['rate'] !== null ? '%'.$a['rate'] : '—' }}</td></tr>
@endforeach</table>
<p class="text-xs text-slate-400 mt-2">Devam oranı: (geldi + geç) / (kayıtlar − izinli). %85 altı kırmızı.</p>
@endif
@endsection
