@extends('layouts.app')
@section('title', 'Ödevlerim')
@section('content')
<x-s-head icon="📝" title="Ödevlerim" sub="Süresi yaklaşanları önce yap!" />
<div style="display:grid;gap:.9rem;max-width:760px" class="rise">
@forelse($assignments as $a)
  @php($s = $subs[$a->id] ?? null)
  @php($done = (bool) $s?->completed_at)
  @php($late = !$done && $a->due_date && $a->due_date->isPast() && !$a->due_date->isToday())
  <div class="s-card" style="display:flex;align-items:center;gap:1rem;{{ $done ? 'background:#f0fdf4;border-color:#bbf7d0' : ($late ? 'border-color:#fbcfe8' : '') }}">
    <span style="font-size:2.3rem">{{ $done ? '✅' : ($a->type === 'game' ? '🎮' : '📖') }}</span>
    <div style="flex:1;min-width:0">
      <h3 style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $a->title }}</h3>
      <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.35rem">
        <span class="s-chip">{{ $a->type === 'course' ? ($a->course?->name ?? 'Ders') : config('games.'.$a->game_slug) }}</span>
        <span class="s-chip {{ $late ? 'pink' : '' }}">📅 {{ $a->due_date ? ($late ? 'Süresi geçti · ' : 'Son: ').$a->due_date->format('d.m.Y') : 'Süresiz' }}</span>
        <span class="s-chip warn">🪙 {{ $done ? '+'.$s->xp_awarded : $a->xp_reward }} XP</span>
      </div>
    </div>
    @if($done)<span class="s-chip ok">Tamamlandı</span>@else<a class="s-btn" href="{{ route('student.assignments.open', $a) }}">Başla</a>@endif
  </div>
@empty
  <div class="s-card" style="text-align:center;padding:3rem 1rem"><div style="font-size:3.2rem">🎉</div><h3>Hiç ödevin yok!</h3><p style="color:#6b7280;font-weight:600">Dinlen, oyun oyna ya da bir ders çalış.</p></div>
@endforelse
</div>
@endsection
