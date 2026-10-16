@extends('layouts.app')
@section('title', 'Etkinlikler')
@section('content')
@php($student = auth()->user()->hasRole('student'))
@if($student)
<x-s-head icon="🎮" title="Oyunlar" sub="Oyna, öğren, XP topla!" />
<div class="s-grid rise" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))">
@forelse($slugs as $slug)
  @php($cfg = config("runners.$slug"))
  @php($levels = $cfg['levels'] ?? 1)
  @php($doneN = $progress[$slug] ?? 0)
  @php($ready = in_array($cfg['kind'], ['native', 'route']))
  <a href="{{ $ready ? route('games.play', $slug) : '#' }}" class="s-card" style="padding:0;overflow:hidden;{{ $ready ? '' : 'opacity:.6;pointer-events:none' }}">
    <div class="s-banner g{{ crc32($slug) % 6 }}" style="height:120px"><span style="font-size:4rem">{{ $cfg['emoji'] }}</span>
      <b class="s-chip {{ $doneN >= $levels ? 'ok' : ($doneN ? 'warn' : '') }}" style="position:absolute;top:.7rem;right:.7rem">{{ $doneN >= $levels ? '🏆 Bitti' : ($doneN ? '▶️ Devam' : ($ready ? '✨ Yeni' : '🔧 Yakında')) }}</b></div>
    <div style="padding:.9rem 1.2rem 1.2rem">
      <h3>{{ config("games.$slug") }}</h3>
      <div style="display:flex;justify-content:space-between;font-size:.78rem;font-weight:800;color:var(--brand);margin:.6rem 0 .25rem"><span>Seviye</span><span>{{ $doneN }} / {{ $levels }}</span></div>
      <div class="xpbar" style="height:10px"><i data-pct="{{ round($doneN / max(1, $levels) * 100) }}" style="width:0"></i></div>
    </div>
  </a>
@empty
  <div class="s-card" style="grid-column:1/-1;text-align:center;padding:3rem 1rem"><div style="font-size:3.2rem">🔒</div><h3>Henüz oyun açılmamış</h3><p style="color:#6b7280;font-weight:600">Öğretmenin oyunları açınca burada görünecek.</p></div>
@endforelse
</div>
@else
<div class="page-head"><div><h1>Etkinlikler</h1><p style="color:var(--muted);margin:.2rem 0 0">Öğrencilerinizin oynayabileceği kodlama oyunları{{ auth()->user()->hasRole('teacher') ? ' (size açılanlar)' : '' }}.</p></div></div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:1rem" class="rise">
@forelse($slugs as $slug)
  @php($cfg = config("runners.$slug"))
  <a href="{{ route('games.play', $slug) }}" class="card card-pad" style="text-decoration:none;color:inherit;transition:transform .2s,box-shadow .2s" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform=''">
    <div style="font-size:2.4rem">{{ $cfg['emoji'] }}</div><b style="display:block;margin:.4rem 0 .5rem">{{ config("games.$slug") }}</b>
    <span class="badge {{ in_array($cfg['kind'], ['native', 'route']) ? 'badge-ok' : '' }}">{{ in_array($cfg['kind'], ['native', 'route']) ? 'Hazır' : 'Paket / yakında' }}</span>
    <span class="badge" style="margin-left:.2rem">{{ $cfg['levels'] ?? 1 }} seviye</span>
  </a>
@empty<div class="card empty" style="grid-column:1/-1"><div class="empty-i">🔒</div>Size henüz etkinlik açılmamış. Yöneticiden isteyin.</div>@endforelse
</div>

@if(auth()->user()->hasRole('admin'))
<h2 style="font-weight:800;font-size:1.25rem;margin:2.2rem 0 .3rem">Öğretmenlere etkinlik aç</h2>
<p style="color:var(--muted);margin:0 0 1rem;font-size:.9rem">Bir öğretmene açılan oyunları, o öğretmenin sınıfındaki öğrenciler oynayabilir.</p>
<div style="display:grid;gap:1rem">
@foreach($teachers as $t)
  <form method="POST" action="{{ route('games.assign') }}" class="card card-pad">@csrf
    <input type="hidden" name="teacher_id" value="{{ $t->id }}">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem"><b>{{ $t->user->name }}</b><button class="btn btn-primary btn-sm">Kaydet</button></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:.5rem">
    @foreach(config('games') as $slug => $name)
      <label style="display:flex;gap:.5rem;align-items:center;font-size:.86rem;padding:.4rem .6rem;border:1px solid var(--line);border-radius:10px;cursor:pointer"><input type="checkbox" name="games[]" value="{{ $slug }}" @checked(in_array($slug, ($unlocked[$t->id] ?? collect())->all()))> {{ config("runners.$slug.emoji") }} {{ $name }}</label>
    @endforeach
    </div>
  </form>
@endforeach
</div>
@endif
@endif
@endsection
