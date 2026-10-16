@extends('layouts.app')
@section('title', 'Etkinlik geçmişi')
@section('content')
<h1 class="text-2xl font-bold mb-1">{{ $student->user->name }}</h1>
<p class="text-slate-500 mb-4">{{ $student->schoolClass?->label }} · Toplam süre {{ intdiv($student->total_seconds, 60) }} dk · <a class="text-indigo-600" href="{{ route('reports.show', $student) }}">Karne</a></p>
<table class="w-full max-w-2xl bg-white rounded-xl shadow text-sm">
@forelse($logs as $l)<tr class="border-b"><td class="p-2 text-slate-400">{{ $l->created_at->format('d.m H:i:s') }}</td><td>{{ $l->route_name }}</td><td class="text-slate-500">/{{ $l->label }}</td></tr>
@empty<tr><td class="p-3 text-slate-500">Kayıt yok.</td></tr>@endforelse
</table>
@endsection
