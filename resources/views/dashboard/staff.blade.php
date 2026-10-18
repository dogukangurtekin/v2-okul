@extends('layouts.app')
@section('title', 'Panel')
@section('content')
<div class="page-head">
  <div><h1>Merhaba, {{ $name }} 👋</h1><p style="color:var(--muted);margin:.2rem 0 0">{{ now()->locale('tr')->isoFormat('D MMMM YYYY, dddd') }} · işte okulunuzun bugünkü özeti</p></div>
  <div style="display:flex;flex-wrap:wrap;gap:.5rem">
    <a class="btn btn-primary" href="{{ route('attendance.sheet') }}"><x-icon name="calendar" style="width:17px;height:17px" />Yoklama al</a>
    <a class="btn btn-ghost" href="{{ route('assignments.create') }}"><x-icon name="clipboard" style="width:17px;height:17px" />Ödev ver</a>
    <a class="btn btn-ghost" href="{{ route('live.index') }}"><x-icon name="bolt" style="width:17px;height:17px" />Canlı oturum</a>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:1rem" class="rise">
  @foreach([
    ['Öğrenci', $stats['students'], 'cap', 'var(--tint-brand)', '#2447f5', route('students.index')],
    ['Sınıf', $stats['classes'], 'building', 'var(--tint-info)', '#0891b2', route('classes.index')],
    ['Ders', $stats['courses'], 'book', '#fef3c7', '#d97706', route('courses.index')],
    ['Şu an çevrimiçi', $stats['active'], 'monitor', '#dcfce7', '#16a34a', route('tracking.index')],
  ] as [$l, $n, $ic, $bg, $fg, $href])
    <a href="{{ $href }}" class="card stat" style="text-decoration:none;color:inherit;transition:transform .2s,box-shadow .2s" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform=''">
      <span class="stat-i" style="background:{{ $bg }};color:{{ $fg }}"><x-icon :name="$ic" style="width:20px;height:20px" /></span>
      <div class="stat-n" data-count="{{ $n }}">{{ $n }}</div><div class="stat-l">{{ $l }}</div>
    </a>
  @endforeach
</div>

<div style="display:grid;grid-template-columns:minmax(0,1.6fr) minmax(0,1fr);gap:1rem;margin-top:1rem" class="dash-grid">
  <section class="card card-pad">
    <div style="display:flex;justify-content:space-between;align-items:baseline"><h2 style="font-weight:700;margin:0">Öğrenci etkinliği</h2><span class="badge">son 7 gün</span></div>
    <div style="display:flex;align-items:flex-end;gap:.7rem;height:170px;margin-top:1.1rem">
      @foreach($chart as $b)
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:.4rem;height:100%;justify-content:flex-end">
          <small style="color:var(--muted);font-weight:600">{{ $b['n'] ?: '' }}</small>
          <div style="width:100%;max-width:46px;border-radius:10px 10px 4px 4px;height:{{ max(6, round($b['n'] / $chartMax * 100)) }}%;background:{{ $b['today'] ? 'linear-gradient(180deg,#2447f5,var(--brand))' : 'var(--tint-brand-bd)' }};transition:height .8s var(--ease)"></div>
          <small style="font-weight:{{ $b['today'] ? 800 : 600 }};color:{{ $b['today'] ? 'var(--brand)' : 'var(--muted)' }}">{{ $b['label'] }}</small>
        </div>
      @endforeach
    </div>
    @if(array_sum(array_column($chart, 'n')) === 0)<p class="empty" style="padding:.5rem 0 0">Henüz öğrenci etkinliği yok. Öğrenciler giriş yaptıkça burada görünür.</p>@endif
  </section>

  <section class="card card-pad">
    <h2 style="font-weight:700;margin:0 0 .9rem">Bugün yapılacaklar</h2>
    @php($att = $attendance)
    <a href="{{ route('attendance.sheet') }}" style="display:block;text-decoration:none;color:inherit;margin-bottom:1rem">
      <div style="display:flex;justify-content:space-between;font-size:.88rem;font-weight:600"><span>📅 Yoklama</span><span>{{ $att['taken'] }} / {{ $att['total'] }} sınıf</span></div>
      <div style="height:8px;border-radius:99px;background:var(--tint-brand);margin-top:.4rem;overflow:hidden"><div data-pct="{{ $att['total'] ? round($att['taken'] / $att['total'] * 100) : 0 }}" style="height:100%;background:linear-gradient(90deg,#2447f5,#22c55e);border-radius:inherit;transition:width 1s var(--ease)"></div></div>
    </a>
    <div style="display:grid;gap:.5rem;font-size:.9rem">
      @if($support)<a href="{{ route('support.index') }}" class="btn btn-soft" style="justify-content:flex-start">🛟 {{ $support }} okunmamış destek talebi</a>@endif
      <a href="{{ route('live.index') }}" class="btn btn-ghost" style="justify-content:flex-start">⚡ {{ $liveCount }} açık canlı oturum</a>
      <a href="{{ route('tracking.index') }}" class="btn btn-ghost" style="justify-content:flex-start">🖥️ Canlı takibi aç</a>
      <a href="{{ route('analytics.index') }}" class="btn btn-ghost" style="justify-content:flex-start">📊 Başarı analizi</a>
    </div>
  </section>
</div>

<div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:1rem;margin-top:1rem" class="dash-grid">
  <section class="card card-pad">
    <h2 style="font-weight:700;margin:0 0 .8rem">🏆 En çok XP kazananlar</h2>
    @forelse($top as $i => $s)
      <div style="display:flex;align-items:center;gap:.8rem;padding:.55rem 0;{{ $loop->last ? '' : 'border-bottom:1px solid var(--line)' }}">
        <span style="width:26px;text-align:center;font-weight:800;color:{{ ['#f59e0b','#94a3b8','#b45309'][$i] ?? 'var(--line)' }}">{{ $i + 1 }}</span>
        <span style="font-size:1.5rem">{{ $s->avatar?->emoji ?? '🙂' }}</span>
        <div style="flex:1;min-width:0"><b style="font-size:.9rem">{{ $s->user->name }}</b><div style="font-size:.75rem;color:var(--muted)">{{ $s->schoolClass?->label ?? '—' }}</div></div>
        <span class="badge badge-brand">{{ $s->xp_total }} XP</span>
      </div>
    @empty<div class="empty"><div class="empty-i">🎯</div>Öğrenciler XP kazandıkça burada sıralanır.</div>@endforelse
  </section>
  <section class="card card-pad">
    <h2 style="font-weight:700;margin:0 0 .8rem">⚡ Son XP hareketleri</h2>
    @forelse($recent as $e)
      <div style="display:flex;align-items:center;gap:.7rem;padding:.5rem 0;{{ $loop->last ? '' : 'border-bottom:1px solid var(--line)' }}">
        <span class="badge {{ $e->delta >= 0 ? 'badge-ok' : 'badge-bad' }}">{{ $e->delta >= 0 ? '+' : '' }}{{ $e->delta }}</span>
        <div style="flex:1;min-width:0;font-size:.88rem"><b>{{ $e->student->user->name }}</b> <span style="color:var(--muted)">· {{ \Illuminate\Support\Str::limit($e->reason, 42) }}</span></div>
        <small style="color:#94a3b8;white-space:nowrap">{{ $e->created_at->diffForHumans(null, true) }}</small>
      </div>
    @empty<div class="empty"><div class="empty-i">🌱</div>Henüz XP hareketi yok.</div>@endforelse
  </section>
</div>

@foreach($announcements as $x)
  <section class="card card-pad" style="margin-top:1rem;border-left:5px solid #f59e0b"><b>📢 {{ $x->title }}</b><div style="color:#475569;margin-top:.25rem;font-size:.9rem">{{ \Illuminate\Support\Str::limit($x->content, 220) }}</div></section>
@endforeach
<style>@media (max-width: 900px) { .dash-grid { grid-template-columns: minmax(0,1fr) !important; } }</style>
@endsection
