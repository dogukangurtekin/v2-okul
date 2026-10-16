@extends('layouts.app')
@section('title', 'Derslerim')
@section('content')
<x-s-head icon="📚" title="Derslerim" sub="Dersleri bitir, XP kazan, seviye atla!" />
@php($emojis = ['🧠', '💻', '🎨', '🔬', '🚀', '🌈', '🧩', '🤖'])
<div class="s-grid rise" style="grid-template-columns:repeat(auto-fill,minmax(270px,1fr))">
@forelse($courses as $c)
  @php($p = $progress[$c->id] ?? null)
  @php($done = (bool) $p?->completed_at)
  @php($total = max(1, $c->slides_count))
  @php($pct = $done ? 100 : ($p ? min(99, round(($p->slide_index + 1) / $total * 100)) : 0))
  <a href="{{ route('student.courses.show', $c) }}" class="s-card" style="padding:0;overflow:hidden">
    <div class="s-banner g{{ $c->id % 6 }}">@if($c->cover_path)<img src="{{ asset('storage/'.$c->cover_path) }}" alt="" loading="lazy">@else<span>{{ $emojis[$c->id % count($emojis)] }}</span>@endif
      <b class="s-chip {{ $done ? 'ok' : ($p ? 'warn' : '') }}" style="position:absolute;top:.7rem;right:.7rem">{{ $done ? '✅ Tamamlandı' : ($p ? '▶️ Devam ediyor' : '✨ Yeni') }}</b></div>
    <div style="padding:1rem 1.2rem 1.2rem">
      <h3>{{ $c->name }}</h3>
      <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin:.5rem 0 .8rem">@if($c->category)<span class="s-chip">{{ $c->category }}</span>@endif @if($c->difficulty)<span class="s-chip pink">{{ $c->difficulty }}</span>@endif<span class="s-chip">{{ $c->slides_count }} slayt</span></div>
      <div class="xpbar" style="height:10px"><i data-pct="{{ $pct }}" style="width:0"></i></div>
      <div style="font-size:.78rem;font-weight:800;color:#7c3aed;margin-top:.3rem">%{{ $pct }}</div>
    </div>
  </a>
@empty
  <div class="s-card" style="grid-column:1/-1;text-align:center;padding:3rem 1rem"><div style="font-size:3.2rem">🌱</div><h3>Sınıfına atanmış ders yok</h3><p style="color:#6b7280;font-weight:600">Öğretmenin ders atayınca burada görünecek.</p></div>
@endforelse
</div>
@endsection
