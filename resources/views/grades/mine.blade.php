@extends('layouts.app')
@section('title', 'Notlarım')
@section('content')
<h1 class="text-2xl font-bold mb-4">Notlarım</h1>
@forelse($byTerm as $term => $list)
<div class="bg-white rounded-xl shadow p-4 mb-4 max-w-2xl"><div class="flex justify-between font-semibold mb-2"><span>{{ $term }}</span><span>Ortalama: {{ $svc->weightedAverage($list) ?? '—' }}</span></div>
<table class="w-full text-sm">@foreach($list as $g)<tr class="border-b"><td class="py-1">{{ config('school.exam_types')[$g->exam_type]['label'] }} {{ $g->title }}</td><td class="text-slate-400">{{ $g->course?->name }}</td><td class="text-slate-400">{{ $g->exam_date->format('d.m.Y') }}</td><td class="text-right font-semibold">{{ rtrim(rtrim(number_format($g->score, 2, ',', ''), '0'), ',') }}</td></tr>@endforeach</table></div>
@empty<p class="text-slate-500">Henüz not yok.</p>@endforelse
@endsection
