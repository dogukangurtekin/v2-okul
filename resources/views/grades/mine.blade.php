@extends('layouts.app')
@section('title', 'Notlarım')
@section('content')
<x-s-head icon="⭐" title="Notlarım" sub="Sınav ve proje notların" />
<div style="max-width:760px;display:grid;gap:1.1rem" class="rise">
@forelse($byTerm as $term => $list)
  @php($avg = $svc->weightedAverage($list))
  <section class="s-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem"><h3>{{ $term }}</h3><span class="s-chip {{ ($avg ?? 100) >= 50 ? 'ok' : 'pink' }}" style="font-size:.85rem">Ortalama: {{ $avg ?? '—' }}</span></div>
    @foreach($list as $g)
      <div style="display:flex;align-items:center;gap:.8rem;padding:.55rem 0;border-top:2px dashed #cfdbe6">
        <div style="flex:1;min-width:0"><b>{{ config('school.exam_types')[$g->exam_type]['label'] }} {{ $g->title }}</b><div style="font-size:.76rem;color:var(--brand);font-weight:700">{{ $g->course?->name }} {{ $g->exam_date->format('d.m.Y') }}</div></div>
        <span class="s-chip {{ $g->score >= 85 ? 'ok' : ($g->score >= 50 ? 'warn' : 'pink') }}" style="font-size:.95rem">{{ rtrim(rtrim(number_format($g->score, 2, ',', ''), '0'), ',') }}</span>
      </div>
    @endforeach
  </section>
@empty<div class="s-card" style="text-align:center;padding:2.5rem"><div style="font-size:2.6rem">📚</div><h3>Henüz notun yok</h3></div>@endforelse
</div>
@endsection
