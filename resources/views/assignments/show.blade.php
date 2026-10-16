@extends('layouts.app')
@section('title', $assignment->title)
@section('content')
<h1 class="text-2xl font-bold">{{ $assignment->title }}</h1>
<p class="text-slate-500 mb-4">{{ $assignment->details }}</p>
@foreach($assignment->classes as $class)
<h2 class="font-semibold mt-4 mb-1">{{ $class->label }}</h2>
<table class="w-full bg-white rounded-xl shadow text-sm">
<tr class="text-left border-b"><th class="p-3">Öğrenci</th><th>Durum</th><th>Süre (sn)</th><th>XP</th></tr>
@foreach($class->students as $st)@php($sub = $subs[$st->id] ?? null)
<tr class="border-b"><td class="p-3">{{ $st->user->name }}</td><td>{{ $sub?->completed_at ? 'Tamamlandı' : ($sub ? 'Başladı' : 'Bekliyor') }}</td><td>{{ $sub?->completion_seconds ?? '—' }}</td><td>{{ $sub?->xp_awarded ?? 0 }}</td></tr>
@endforeach
</table>
@endforeach
@endsection
