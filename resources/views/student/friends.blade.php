@extends('layouts.app')
@section('title', 'Arkadaşlarım')
@section('content')
<x-s-head icon="🤝" title="Sınıf Arkadaşlarım" sub="Birlikte öğrenmek daha eğlenceli!" />
<div class="s-grid rise" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr))">
@forelse($friends as $f)
  @php($lv = \App\Support\Level::forXp($f->xp_total))
  <div class="s-card hov" style="text-align:center"><div style="font-size:3.4rem">{{ $f->avatar?->emoji ?? '🙂' }}</div><h3 style="margin-top:.2rem">{{ $f->user->name }}</h3><span class="s-chip" style="margin-top:.4rem">{{ $lv['icon'] }} Sv. {{ $lv['level'] }}</span></div>
@empty
  <div class="s-card" style="grid-column:1/-1;text-align:center;padding:3rem 1rem"><div style="font-size:3rem">👋</div><h3>Henüz sınıf arkadaşın yok</h3></div>
@endforelse
</div>
@endsection
