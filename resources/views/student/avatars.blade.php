@extends('layouts.app')
@section('title', 'Avatarlarım')
@section('content')
<x-s-head icon="🎭" title="Avatar Mağazası" sub="XP'lerinle yeni karakterler al ve profilinde göster." />
<div style="display:flex;flex-wrap:wrap;gap:.6rem;margin-bottom:1.2rem"><span class="s-chip warn" style="font-size:.9rem">🪙 Harcanabilir: <b data-count="{{ $student->xp_balance }}">{{ $student->xp_balance }}</b> XP</span><span class="s-chip" style="font-size:.9rem">⭐ Toplam: {{ $student->xp_total }} XP</span></div>
@php($tiers = ['basic' => ['Sade', '#e5e7eb'], 'rare' => ['Nadir', '#93c5fd'], 'epic' => ['Efsanevi', '#c084fc'], 'premium' => ['Altın', '#fbbf24']])
<div class="s-grid rise" style="grid-template-columns:repeat(auto-fill,minmax(190px,1fr))">
@foreach($avatars as $a)
  @php($has = in_array($a->id, $owned))
  @php($locked = !$has && $student->xp_total < $a->required_xp)
  @php($equipped = $student->current_avatar_id === $a->id)
  @php($t = $tiers[$a->tier] ?? $tiers['basic'])
  <div class="s-card" style="text-align:center;border-color:{{ $equipped ? '#22c55e' : $t[1] }};{{ $equipped ? 'background:#f0fdf4' : '' }}">
    <div style="font-size:4.2rem;line-height:1.1;{{ $locked ? 'filter:grayscale(1) blur(1px);opacity:.5' : '' }}" class="{{ $equipped ? 's-avatar' : '' }}">{{ $a->emoji }}</div>
    <h3 style="margin-top:.4rem">{{ $a->name }}</h3>
    <span class="s-chip" style="background:{{ $t[1] }}33;color:var(--ink);margin:.4rem 0 .8rem">{{ $t[0] }}</span>
    @if($equipped)<div class="s-chip ok" style="display:flex;justify-content:center">✅ Kullanılıyor</div>
    @elseif($has)<form method="POST" action="{{ route('student.avatars.equip', $a) }}">@csrf<button class="s-btn green" style="width:100%">Kullan</button></form>
    @elseif($locked)<div class="s-chip" style="display:flex;justify-content:center">🔒 {{ $a->required_xp }} toplam XP gerekir</div>
    @else<form method="POST" action="{{ route('student.avatars.buy', $a) }}">@csrf<button class="s-btn amber" style="width:100%">🪙 {{ $a->price }} XP · Al</button></form>@endif
  </div>
@endforeach
</div>
@endsection
