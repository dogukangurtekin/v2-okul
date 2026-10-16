@extends('layouts.app')
@section('title', 'Devamsızlık')
@section('content')
<x-s-head icon="🗓️" title="Devamsızlığım" sub="Derse düzenli gelmek başarının anahtarı!" />
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.9rem;max-width:760px;margin-bottom:1.3rem" class="rise">
@foreach([['Devam', $sum['rate'] !== null ? '%'.$sum['rate'] : '—', '🌟'], ['Geldi', $sum['present'], '✅'], ['Geç', $sum['late'], '⏰'], ['İzinli', $sum['excused'], '📄'], ['Gelmedi', $sum['absent'], '❌']] as [$k, $v, $e])
  <div class="s-card" style="text-align:center;padding:1rem"><div style="font-size:1.6rem">{{ $e }}</div><div style="font-family:var(--font-display);font-size:1.7rem;font-weight:700">{{ $v }}</div><div style="font-size:.75rem;font-weight:800;color:var(--brand)">{{ $k }}</div></div>
@endforeach
</div>
<div style="max-width:760px;display:grid;gap:.5rem">
@forelse($records as $r)
  <div class="s-card" style="display:flex;align-items:center;gap:.8rem;padding:.6rem 1rem"><b style="width:6.2rem">{{ $r->date->format('d.m.Y') }}</b><span style="flex:1;color:#6b7280;font-weight:700">{{ $r->course?->name ?? 'Günlük yoklama' }}{{ $r->note ? ' · '.$r->note : '' }}</span>
    <span class="s-chip {{ ['present' => 'ok', 'late' => 'warn', 'excused' => '', 'absent' => 'pink'][$r->status] }}">{{ config('school.attendance_statuses')[$r->status] }}</span></div>
@empty<div class="s-card" style="text-align:center;padding:2rem"><div style="font-size:2.6rem">🎈</div><h3>Henüz kayıt yok</h3></div>@endforelse
</div>
@endsection
