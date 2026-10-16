@php($first = explode(' ', trim($student->user->name))[0])
@php($wmax = max(1, collect($week)->max('xp')))
@php($active = collect($heat)->where('n', '>', 0)->count())
<div class="rise t-dash">
  <header class="t-top">
    <div><h1>Merhaba, {{ $first }}</h1><p>{{ $student->schoolClass?->label }} · {{ now()->translatedFormat('d F, l') }}</p></div>
    @if($live)<a href="{{ route('student.live') }}" class="t-live"><span class="dot"></span>Canlı oturum açık · katıl</a>@endif
  </header>

  <section class="t-read">
    <div><small>seviye</small><b>{{ $lv['level'] }}</b><span>{{ $lv['title'] }}</span></div>
    <div><small>toplam xp</small><b data-count="{{ $lv['xp'] }}">{{ $lv['xp'] }}</b><span>{{ $lv['remaining'] }} sonraki seviyeye</span></div>
    <div><small>sınıf sırası</small><b>{{ $rank ? '#'.$rank : '–' }}</b><span>{{ $classSize > 1 ? $classSize.' öğrenci' : 'sınıf' }}</span></div>
    <div><small>seri</small><b>{{ $streak }}<em>gün</em></b><span>{{ $active }} aktif gün / 12 hafta</span></div>
    <div class="t-bar"><i data-pct="{{ $lv['pct'] }}" style="width:{{ $lv['pct'] }}%"></i></div>
  </section>

  <div class="t-two">
    <section class="s-card t-panel">
      <h3>Etkinlik · son 12 hafta <em>{{ $active }} gün</em></h3>
      <div class="t-heat">@foreach($heat as $h)<i class="l{{ min(3, $h['n']) }}" title="{{ $h['d'] }} · {{ $h['n'] }} kazanım"></i>@endforeach</div>
      <div class="t-legend"><span>az</span><i class="l0"></i><i class="l1"></i><i class="l2"></i><i class="l3"></i><span>çok</span></div>
    </section>
    <section class="s-card t-panel">
      <h3>Son 7 gün · XP</h3>
      <div class="t-bars">@foreach($week as $d)<div class="{{ $d['today'] ? 'today' : '' }}"><em>{{ $d['xp'] ?: '' }}</em><i style="height:{{ max(3, round($d['xp'] / $wmax * 100)) }}%"></i><span>{{ $d['label'] }}</span></div>@endforeach</div>
    </section>
  </div>

  @if($gameCards->isNotEmpty())
  <section>
    <div class="t-sec"><h2>Oyunlar</h2><a href="{{ route('games.index') }}">tümü</a></div>
    <div class="t-games">@foreach($gameCards as $g)
      <a href="{{ route('games.play', $g['slug']) }}" class="s-card t-game"><span class="e">{{ $g['emoji'] }}</span><div><b>{{ $g['name'] }}</b><small>{{ $g['done'] }}/{{ $g['total'] }}</small>
        <div class="t-line"><i data-pct="{{ round($g['done'] / max(1, $g['total']) * 100) }}" style="width:0"></i></div></div></a>
    @endforeach</div>
  </section>
  @endif

  <div class="t-cols">
    <div style="display:grid;gap:1rem;align-content:start">
      <section class="s-card t-panel">
        <h3>Yapılacaklar <em>{{ $pending->count() }}</em></h3>
        @forelse($pending->take(6) as $a)
          @php($late = $a->due_date && $a->due_date->isPast() && ! $a->due_date->isToday())
          <a href="{{ route('student.assignments.open', $a) }}" class="t-row"><span class="tag">{{ $a->type === 'game' ? 'OYUN' : 'DERS' }}</span><b>{{ $a->title }}</b>
            <span class="due {{ $late ? 'late' : '' }}">{{ $a->due_date ? $a->due_date->format('d.m') : '—' }}</span><span class="xp">+{{ $a->xp_reward }}</span></a>
        @empty
          <p class="t-none">Bekleyen ödev yok.</p>
        @endforelse
      </section>
      @if($courseCards->isNotEmpty())
      <section class="s-card t-panel">
        <h3>Dersler <a href="{{ route('student.courses') }}">tümü</a></h3>
        @foreach($courseCards as $cc)@php($c = $cc['course'])
          <a href="{{ route('student.courses.show', $c) }}" class="t-crs"><b>{{ $c->name }}</b><small>{{ $cc['pct'] }}%</small><div class="t-line"><i data-pct="{{ $cc['pct'] }}" style="width:0"></i></div></a>
        @endforeach
      </section>
      @endif
      @foreach($announcements as $x)
        <section class="s-card t-panel"><h3>Duyuru</h3><b>{{ $x->title }}</b><p class="t-none" style="text-align:left;margin:.3rem 0 0">{{ \Illuminate\Support\Str::limit($x->content, 200) }}</p></section>
      @endforeach
    </div>
    <aside style="display:grid;gap:1rem;align-content:start">
      <section class="s-card t-panel">
        <h3>Sıralama <a href="{{ route('student.ranking') }}">tümü</a></h3>
        @foreach($topFive as $t)
          <div class="t-rank {{ $t->id === $student->id ? 'me' : '' }}"><span class="n">{{ $loop->iteration }}</span><span class="a">{{ $t->avatar?->emoji ?? '🙂' }}</span><b>{{ $t->id === $student->id ? 'sen' : explode(' ', trim($t->user->name))[0] }}</b><em>{{ $t->xp_total }}</em></div>
        @endforeach
      </section>
      <section class="s-card t-panel">
        <h3>Aktivite</h3>
        @forelse($recentXp as $x)<div class="t-feed"><em>+{{ $x->delta }}</em><span>{{ $x->reason }}</span><small>{{ $x->created_at->diffForHumans(null, true, true) }}</small></div>
        @empty<p class="t-none">Henüz kayıt yok.</p>@endforelse
      </section>
      @if($nextBadge)
      <section class="s-card t-panel">
        <h3>Sıradaki rozet <a href="{{ route('student.badges') }}">tümü</a></h3>
        <div class="t-next"><span>{{ $nextBadge->emoji }}</span><div><b>{{ $nextBadge->name }}</b><small>{{ max(0, $nextBadge->xp_threshold - $lv['xp']) }} XP kaldı</small></div></div>
        <div class="t-line" style="margin-top:.6rem"><i data-pct="{{ min(100, round($lv['xp'] / max(1, $nextBadge->xp_threshold) * 100)) }}" style="width:0"></i></div>
      </section>
      @endif
    </aside>
  </div>
</div>
