@extends('layouts.app')
@section('title', 'Sıralama')
@section('content')
<x-s-head icon="🏆" title="Sınıf Sıralaması" sub="En çok XP toplayan kim?" />
@php($podium = $top->take(3))
@if($podium->count() >= 2)
  @php($order = [1, 0, 2])
  <div style="display:flex;justify-content:center;align-items:flex-end;gap:.9rem;margin:0 auto 1.6rem;max-width:560px" class="rise">
    @foreach($order as $pos)@continue(! isset($podium[$pos]))
      @php($s = $podium[$pos])
      <div style="flex:1;text-align:center">
        <div style="font-size:{{ $pos === 0 ? '4.2rem' : '3.2rem' }};{{ $pos === 0 ? 'animation:bob 3s ease-in-out infinite' : '' }}">{{ $s->avatar?->emoji ?? '🙂' }}</div>
        <b style="display:block;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ explode(' ', $s->user->name)[0] }}</b>
        <div style="margin-top:.4rem;border-radius:1.2rem 1.2rem 0 0;color:#fff;font-family:var(--font-display);font-weight:700;padding-top:.6rem;height:{{ [150, 110, 84][$pos] }}px;background:linear-gradient(180deg,{{ [['#fbbf24','#f59e0b'], ['#cbd5e1','#94a3b8'], ['#fb923c','#c2410c']][$pos][0] }},{{ [['#fbbf24','#f59e0b'], ['#cbd5e1','#94a3b8'], ['#fb923c','#c2410c']][$pos][1] }})">
          <div style="font-size:1.8rem">{{ ['🥇', '🥈', '🥉'][$pos] }}</div><div style="font-size:.9rem">{{ $s->xp_total }} XP</div></div>
      </div>
    @endforeach
  </div>
@endif
<div style="max-width:620px;display:grid;gap:.6rem" class="rise">
  @foreach($top as $i => $s)
    <div class="s-card" style="display:flex;align-items:center;gap:.9rem;padding:.7rem 1rem;{{ $s->id === $student->id ? 'background:#fff3c4;border-color:#8fb5ff' : '' }}">
      <b style="width:2rem;text-align:center;font-family:var(--font-display);font-size:1.15rem;color:{{ ['#b45309', '#64748b', '#c2410c'][$i] ?? '#8fb5ff' }}">{{ $i + 1 }}</b>
      <span style="font-size:1.9rem">{{ $s->avatar?->emoji ?? '🙂' }}</span>
      <b style="flex:1">{{ $s->user->name }}@if($s->id === $student->id) <span class="s-chip" style="margin-left:.3rem">Sen</span>@endif</b>
      <span class="s-chip warn">{{ $s->xp_total }} XP</span>
    </div>
  @endforeach
</div>
@endsection
