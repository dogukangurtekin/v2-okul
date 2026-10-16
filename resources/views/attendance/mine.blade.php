@extends('layouts.app')
@section('title', 'Devamsızlık')
@section('content')
<h1 class="text-2xl font-bold mb-4">Devamsızlığım</h1>
<div class="grid grid-cols-5 gap-3 max-w-2xl mb-4">
@foreach([['Devam', $sum['rate'] !== null ? '%'.$sum['rate'] : '—'], ['Geldi', $sum['present']], ['Geç', $sum['late']], ['İzinli', $sum['excused']], ['Gelmedi', $sum['absent']]] as [$k, $v])
<div class="bg-white rounded-xl shadow p-3 text-center"><div class="text-xs text-slate-500">{{ $k }}</div><div class="text-xl font-bold">{{ $v }}</div></div>@endforeach</div>
<table class="w-full max-w-2xl bg-white rounded-xl shadow text-sm">
@forelse($records as $r)<tr class="border-b"><td class="p-2">{{ $r->date->format('d.m.Y') }}</td><td>{{ $r->course?->name ?? 'Günlük' }}</td><td>{{ config('school.attendance_statuses')[$r->status] }}</td><td class="text-slate-400">{{ $r->note }}</td></tr>
@empty<tr><td class="p-3 text-slate-500">Kayıt yok.</td></tr>@endforelse</table>
@endsection
