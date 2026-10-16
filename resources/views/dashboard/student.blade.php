@extends('layouts.app')
@section('title', 'Ana Sayfa')
@section('content')
@if(! $student)
  <div class="s-card" style="text-align:center;padding:3rem 1rem"><div style="font-size:3.5rem">🧩</div><h3>Hesabın henüz bir öğrenci kaydına bağlı değil</h3><p style="color:#6b7280">Öğretmenine haber ver.</p></div>
@else
@php($emoji = $student->avatar?->emoji ?? '🙂')
<div class="rise" style="display:grid;gap:1.1rem">
  <section class="s-hero">
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:1.4rem">
      <div class="s-avatar">{{ $emoji }}</div>
      <div style="flex:1;min-width:240px">
        <div style="font-family:var(--font-display);font-size:clamp(1.5rem,3.4vw,2.2rem);font-weight:700;line-height:1.15">Merhaba {{ explode(' ', trim($student->user->name))[0] }}! 👋</div>
        <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin:.7rem 0 .9rem">
          <span class="s-pill">{{ $lv['icon'] }} Seviye {{ $lv['level'] }} · {{ $lv['title'] }}</span>
          @if($rank)<span class="s-pill">👑 Sınıfta {{ $rank }}. sıradasın @if($classSize > 1)<span style="opacity:.75">/ {{ $classSize }}</span>@endif</span>@endif
          <span class="s-pill">🪙 {{ $student->xp_balance }} harcanabilir XP</span>
        </div>
        <div class="xpbar"><i data-pct="{{ $lv['pct'] }}" style="width:{{ $lv['pct'] }}%"></i></div>
        <div style="display:flex;justify-content:space-between;font-size:.82rem;font-weight:800;margin-top:.35rem;opacity:.95"><span><span data-count="{{ $lv['xp'] }}">{{ $lv['xp'] }}</span> XP</span><span>Seviye {{ $lv['level'] + 1 }} için {{ $lv['remaining'] }} XP kaldı</span></div>
      </div>
    </div>
  </section>

  @if($live)
    <a href="{{ route('student.live') }}" class="s-card pulse" style="border-color:#f9a8d4;background:linear-gradient(120deg,#fdf2f8,#fff7ed);display:flex;align-items:center;gap:1rem;border-radius:1.5rem">
      <span style="font-size:2.4rem;animation:wiggle 1s infinite">⚡</span>
      <div style="flex:1"><h3>Sınıfında canlı bir oturum var!</h3><div style="color:#9d174d;font-weight:700;font-size:.9rem">Hemen katıl, XP kazan</div></div><span class="s-btn amber">Katıl</span>
    </a>
  @endif

  <div class="s-grid rise">
    <a class="s-card" href="{{ route('student.courses') }}"><div style="font-size:2.4rem">📚</div><h3>Derslerim</h3><div class="s-chip" style="margin-top:.5rem">{{ $stats['courses_done'] }} / {{ $stats['courses_total'] }} tamam</div></a>
    <a class="s-card" href="{{ route('student.assignments') }}"><div style="font-size:2.4rem">📝</div><h3>Ödevlerim</h3><div class="s-chip {{ $pending->isEmpty() ? 'ok' : 'warn' }}" style="margin-top:.5rem">{{ $pending->isEmpty() ? 'Hepsi tamam 🎉' : $pending->count().' bekliyor' }}</div></a>
    <a class="s-card" href="{{ route('games.index') }}"><div style="font-size:2.4rem">🎮</div><h3>Oyunlar</h3><div class="s-chip pink" style="margin-top:.5rem">{{ $stats['games'] }} oyun açık</div></a>
    <a class="s-card" href="{{ route('student.avatars') }}"><div style="font-size:2.4rem">🎭</div><h3>Avatar mağazası</h3><div class="s-chip" style="margin-top:.5rem">🪙 {{ $student->xp_balance }} XP</div></a>
    <a class="s-card" href="{{ route('student.badges') }}"><div style="font-size:2.4rem">🏅</div><h3>Rozetlerim</h3><div class="s-chip" style="margin-top:.5rem">{{ count($earned) }} / {{ $badges->count() }}</div></a>
    <a class="s-card" href="{{ route('student.ranking') }}"><div style="font-size:2.4rem">🏆</div><h3>Sıralama</h3><div class="s-chip" style="margin-top:.5rem">{{ $rank ? $rank.'. sıradasın' : 'Sınıfını gör' }}</div></a>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.1rem">
    @if($resume)
      <section class="s-card" style="background:linear-gradient(135deg,#eff6ff,#f5f3ff)">
        <div class="s-chip">▶️ Kaldığın yerden devam et</div>
        <h3 style="margin:.6rem 0 .8rem;font-size:1.3rem">{{ $resume['course']->name }}</h3>
        <div class="xpbar" style="height:12px"><i data-pct="{{ round($resume['at'] / $resume['total'] * 100) }}" style="width:0"></i></div>
        <div style="font-size:.82rem;font-weight:800;color:#6d28d9;margin:.35rem 0 1rem">Slayt {{ $resume['at'] }} / {{ $resume['total'] }}</div>
        <a class="s-btn" href="{{ route('student.courses.show', $resume['course']) }}">Devam et →</a>
      </section>
    @endif
    <section class="s-card">
      <h3 style="margin-bottom:.7rem">📝 Yapılacak ödevler</h3>
      @forelse($pending->take(4) as $a)
        @php($late = $a->due_date && $a->due_date->isPast() && ! $a->due_date->isToday())
        <a href="{{ route('student.assignments.open', $a) }}" style="display:flex;align-items:center;gap:.7rem;padding:.6rem .7rem;margin:0 -.7rem;border-radius:14px;text-decoration:none;color:inherit;transition:background .15s" onmouseover="this.style.background='#faf5ff'" onmouseout="this.style.background=''">
          <span style="font-size:1.5rem">{{ $a->type === 'game' ? '🎮' : '📖' }}</span>
          <div style="flex:1;min-width:0"><b style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $a->title }}</b>
            <span class="s-chip {{ $late ? 'pink' : '' }}" style="margin-top:.2rem">{{ $a->due_date ? ($late ? 'Süresi geçti · ' : 'Son: ').$a->due_date->format('d.m') : 'Süresiz' }}</span></div>
          <span class="s-chip warn">+{{ $a->xp_reward }} XP</span>
        </a>
      @empty
        <div style="text-align:center;padding:1.2rem 0;color:#7c3aed;font-weight:800"><div style="font-size:2.6rem">🎉</div>Bekleyen ödevin yok!</div>
      @endforelse
    </section>
  </div>

  <section class="s-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem"><h3>🏅 Rozetlerim</h3><a href="{{ route('student.badges') }}" class="s-chip">Hepsini gör</a></div>
    <div style="display:flex;flex-wrap:wrap;gap:1rem">
      @foreach($badges as $b)@php($has = in_array($b->id, $earned))
        <div title="{{ $b->name }} — {{ $b->description }}" style="text-align:center;width:84px;{{ $has ? '' : 'filter:grayscale(1);opacity:.45' }}">
          <div class="{{ $has ? 'pop' : '' }}" style="font-size:2.4rem;{{ $has ? 'animation-delay:'.($loop->index * .12).'s' : '' }}">{{ $has ? $b->emoji : '🔒' }}</div>
          <div style="font-size:.74rem;font-weight:800">{{ $b->name }}</div><div style="font-size:.68rem;color:#8b5cf6;font-weight:700">{{ $b->xp_threshold }} XP</div>
        </div>
      @endforeach
    </div>
  </section>

  @foreach($announcements as $x)
    <section class="s-card" style="border-color:#fde68a;background:#fffbeb"><h3>📢 {{ $x->title }}</h3><div style="color:#78350f;margin-top:.3rem;font-weight:600;font-size:.92rem">{{ \Illuminate\Support\Str::limit($x->content, 200) }}</div></section>
  @endforeach
</div>
@endif
@endsection
