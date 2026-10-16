@extends('layouts.app')
@section('title', 'Derslerim')
@section('content')
<h1 class="text-2xl font-bold mb-4">Derslerim</h1>
<div class="grid grid-cols-3 gap-4">
@forelse($courses as $c)@php($p = $progress[$c->id] ?? null)
<a href="{{ route('student.courses.show', $c) }}" class="bg-white rounded-xl shadow p-5 block">
<div class="font-semibold">{{ $c->name }}</div><div class="text-sm text-slate-500">{{ $c->slides_count }} slayt</div>
<div class="mt-2 text-sm {{ $p?->completed_at ? 'text-green-600' : 'text-slate-400' }}">{{ $p?->completed_at ? 'Tamamlandı' : ($p ? 'Devam ediyor' : 'Başlanmadı') }}</div></a>
@empty<p class="text-slate-500">Sınıfınıza atanmış ders yok.</p>@endforelse
</div>
@endsection
