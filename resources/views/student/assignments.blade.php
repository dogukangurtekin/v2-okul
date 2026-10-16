@extends('layouts.app')
@section('title', 'Ödevlerim')
@section('content')
<h1 class="text-2xl font-bold mb-4">Ödevlerim</h1>
<div class="space-y-3 max-w-2xl">
@forelse($assignments as $a)@php($s = $subs[$a->id] ?? null)
<div class="bg-white rounded-xl shadow p-4 flex justify-between items-center">
<div><div class="font-semibold">{{ $a->title }}</div><div class="text-sm text-slate-500">{{ $a->type === 'course' ? $a->course?->name : config('games.'.$a->game_slug) }} · Son: {{ $a->due_date?->format('d.m.Y') ?? '—' }} · {{ $a->xp_reward }} XP</div></div>
@if($s?->completed_at)<span class="text-green-600">Tamamlandı</span>@else<a class="bg-indigo-600 text-white px-3 py-1 rounded" href="{{ route('student.assignments.open', $a) }}">Aç</a>@endif
</div>
@empty<p class="text-slate-500">Ödev yok.</p>@endforelse
</div>
@endsection
