@extends('layouts.app')
@section('title', 'Rozetlerim')
@section('content')
@php($next = $badges->first(fn ($b) => !in_array($b->id, $earned)))
<x-s-head icon="🏅" title="Rozetlerim" sub="{{ count($earned) }} / {{ $badges->count() }} rozet topladın" />
@if($next)
  <div class="s-card" style="margin-bottom:1.2rem;max-width:620px;background:linear-gradient(135deg,#fefce8,#fdf2f8)">
    <div style="display:flex;align-items:center;gap:1rem"><span style="font-size:2.6rem;filter:grayscale(.6)">{{ $next->emoji }}</span>
      <div style="flex:1"><h3>Sıradaki rozet: {{ $next->name }}</h3><div style="font-size:.85rem;font-weight:700;color:var(--brand)">{{ $next->description }} · {{ max(0, $next->xp_threshold - $student->xp_total) }} XP kaldı</div>
      <div class="xpbar" style="height:10px;margin-top:.5rem"><i data-pct="{{ min(100, round($student->xp_total / max(1, $next->xp_threshold) * 100)) }}" style="width:0"></i></div></div></div>
  </div>
@endif
<div class="s-grid rise" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr))">
@foreach($badges as $b)
  @php($got = in_array($b->id, $earned))
  <div class="s-card" style="text-align:center;{{ $got ? 'background:linear-gradient(160deg,#fffbeb,#fff);border-color:#fcd34d' : 'opacity:.6' }}">
    <div class="{{ $got ? 'pop' : '' }}" style="font-size:3.6rem;{{ $got ? 'animation-delay:'.($loop->index * .1).'s' : 'filter:grayscale(1)' }}">{{ $got ? $b->emoji : '🔒' }}</div>
    <h3>{{ $b->name }}</h3><div style="font-size:.82rem;color:#6b7280;font-weight:600;min-height:2.2em">{{ $b->description }}</div>
    <span class="s-chip {{ $got ? 'ok' : '' }}" style="margin-top:.5rem">{{ $got ? '✅ Kazanıldı' : $b->xp_threshold.' XP' }}</span>
  </div>
@endforeach
</div>
@endsection
