@php($first = explode(' ', trim($student->user->name))[0])
@php($wmax = max(1, collect($week)->max('xp')))
<div class="rise m-dash">
  <section class="m-hero">
    <div class="m-hero-l">
      <div class="m-hello"><span class="m-av">{{ $student->avatar?->emoji ?? '🙂' }}</span><div><h1>Selam {{ $first }}!</h1><p>{{ $student->schoolClass?->label }} · {{ now()->translatedFormat('d F, l') }}</p></div></div>
      <div class="m-chips">
        <span class="m-chip y">⭐ Seviye {{ $lv['level'] }} · {{ $lv['title'] }}</span>
        @if($streak > 0)<span class="m-chip o">🔥 {{ $streak }} günlük seri</span>@endif
        @if($rank)<span class="m-chip g">🏅 Sınıfta {{ $rank }}. sıra</span>@endif
      </div>
      <div class="m-xp"><div class="m-xp-top"><b><span data-count="{{ $lv['xp'] }}">{{ $lv['xp'] }}</span> XP</b><span>Sonraki seviyeye {{ $lv['remaining'] }} XP</span></div>
        <div class="xpbar"><i data-pct="{{ $lv['pct'] }}" style="width:{{ $lv['pct'] }}%"></i></div></div>
    </div>
    <svg class="m-art" viewBox="0 0 240 170" aria-hidden="true">
      <rect x="30" y="22" width="150" height="104" rx="10" fill="#0f1f66" stroke="#fff" stroke-width="3"/>
      <rect x="30" y="22" width="150" height="20" rx="10" fill="#fff" opacity=".95"/><circle cx="46" cy="32" r="3.5" fill="#ff5c7c"/><circle cx="58" cy="32" r="3.5" fill="#ffc21a"/><circle cx="70" cy="32" r="3.5" fill="#3ddc97"/>
      <rect x="44" y="54" width="52" height="7" rx="3.5" fill="#ffc21a"/><rect x="104" y="54" width="34" height="7" rx="3.5" fill="#8fb5ff"/>
      <rect x="56" y="70" width="72" height="7" rx="3.5" fill="#fff" opacity=".85"/><rect x="56" y="86" width="44" height="7" rx="3.5" fill="#3ddc97"/><rect x="108" y="86" width="40" height="7" rx="3.5" fill="#ff5c7c"/>
      <rect x="44" y="102" width="30" height="7" rx="3.5" fill="#8fb5ff"/>
      <path d="M14 140h172l-12 14H26z" fill="#fff" opacity=".9"/>
      <text x="196" y="70" font-family="monospace" font-weight="700" font-size="30" fill="#ffc21a">&lt;/&gt;</text>
      <path d="M212 100l5 11 12 1-9 8 3 12-11-6-11 6 3-12-9-8 12-1z" fill="#ffc21a" stroke="#14213d" stroke-width="2" stroke-linejoin="round"/>
      <circle cx="20" cy="40" r="6" fill="#ff5c7c"/><circle cx="214" cy="30" r="4" fill="#3ddc97"/>
    </svg>
  </section>

  @if($live)
    <a href="{{ route('student.live') }}" class="m-live"><x-icon name="bolt" /><div><b>Sınıfında canlı bir oturum açık</b><span>Katıl ve XP kazan</span></div><i>Katıl</i></a>
  @endif

  <div class="m-tiles">
    <a href="{{ route('student.courses') }}" class="s-card c1"><span class="ic"><x-icon name="book" /></span><b>{{ $stats['courses_done'] }}<small>/{{ $stats['courses_total'] }}</small></b><span class="lb">Ders tamamlandı</span></a>
    <a href="{{ route('student.assignments') }}" class="s-card c2"><span class="ic"><x-icon name="clipboard" /></span><b>{{ $pending->count() }}</b><span class="lb">Bekleyen ödev</span></a>
    <a href="{{ route('games.index') }}" class="s-card c3"><span class="ic"><x-icon name="gamepad" /></span><b>{{ $stats['games'] }}</b><span class="lb">Açık oyun</span></a>
    <a href="{{ route('student.badges') }}" class="s-card c4"><span class="ic"><x-icon name="medal" /></span><b>{{ count($earned) }}<small>/{{ $badges->count() }}</small></b><span class="lb">Rozet</span></a>
  </div>

  @if($gameCards->isNotEmpty())
  <section>
    <div class="m-sec"><h2>Oyunlar</h2><a href="{{ route('games.index') }}">Hepsi →</a></div>
    <div class="m-games">@foreach($gameCards as $g)
      <a href="{{ route('games.play', $g['slug']) }}" class="s-card m-game"><span class="e t{{ $loop->index % 6 }}">{{ $g['emoji'] }}</span><div><b>{{ $g['name'] }}</b>
        <div class="xpbar" style="margin:.4rem 0 .2rem"><i data-pct="{{ round($g['done'] / max(1, $g['total']) * 100) }}" style="width:0"></i></div><small>{{ $g['done'] }} / {{ $g['total'] }} seviye</small></div></a>
    @endforeach</div>
  </section>
  @endif

  <div class="m-two">
    <section class="s-card">
      <h3 class="m-h">Bu hafta kazandığın XP</h3>
      <div class="m-bars">@foreach($week as $d)<div class="{{ $d['today'] ? 'today' : '' }}"><i style="height:{{ max(4, round($d['xp'] / $wmax * 100)) }}%" title="{{ $d['xp'] }} XP"></i><span>{{ $d['label'] }}</span><em>{{ $d['xp'] ?: '' }}</em></div>@endforeach</div>
    </section>
    <section class="s-card">
      <h3 class="m-h">Yapılacak ödevler</h3>
      @forelse($pending->take(4) as $a)
        @php($late = $a->due_date && $a->due_date->isPast() && ! $a->due_date->isToday())
        <a href="{{ route('student.assignments.open', $a) }}" class="m-row"><x-icon :name="$a->type === 'game' ? 'gamepad' : 'book'" />
          <div><b>{{ $a->title }}</b><span class="{{ $late ? 'late' : '' }}">{{ $a->due_date ? ($late ? 'Süresi geçti · ' : 'Son: ').$a->due_date->format('d.m') : 'Süresiz' }}</span></div><em>+{{ $a->xp_reward }} XP</em></a>
      @empty
        <p class="m-none">Bekleyen ödevin yok. Harika! 🎉</p>
      @endforelse
    </section>
  </div>

  @if($courseCards->isNotEmpty())
  <section>
    <div class="m-sec"><h2>Derslerim</h2><a href="{{ route('student.courses') }}">Hepsi →</a></div>
    <div class="m-courses">@foreach($courseCards as $cc)@php($c = $cc['course'])
      <a href="{{ route('student.courses.show', $c) }}" class="s-card m-course"><div class="s-banner g{{ $c->id % 6 }}">@if($c->cover_path)<img src="{{ asset('storage/'.$c->cover_path) }}" alt="" loading="lazy">@else<span>{{ ['🧠','💻','🎨','🔬','🚀','🌈','🧩','🤖'][$c->id % 8] }}</span>@endif</div>
        <div class="bd"><b>{{ $c->name }}</b><div class="xpbar" style="margin:.5rem 0 .2rem"><i data-pct="{{ $cc['pct'] }}" style="width:0"></i></div><small>{{ $cc['pct'] === 100 ? 'Tamamlandı' : ($cc['started'] ? '%'.$cc['pct'].' · devam et' : 'Yeni ders') }}</small></div></a>
    @endforeach</div>
  </section>
  @endif

  <div class="m-two">
    <section class="s-card">
      <h3 class="m-h">Sınıf sıralaması</h3>
      @foreach($topFive as $t)
        <div class="m-rank {{ $t->id === $student->id ? 'me' : '' }}"><span class="n">{{ ['🥇','🥈','🥉'][$loop->index] ?? $loop->iteration }}</span><span class="a">{{ $t->avatar?->emoji ?? '🙂' }}</span><b>{{ $t->id === $student->id ? 'Sen' : explode(' ', trim($t->user->name))[0] }}</b><em>{{ $t->xp_total }} XP</em></div>
      @endforeach
    </section>
    <section class="s-card">
      <h3 class="m-h">Son kazandıkların</h3>
      @forelse($recentXp as $x)<div class="m-feed"><em>+{{ $x->delta }}</em><span>{{ $x->reason }}</span><small>{{ $x->created_at->diffForHumans() }}</small></div>
      @empty<p class="m-none">Oyun oynayıp ders bitirince XP'lerin burada görünür.</p>@endforelse
    </section>
  </div>

  <section class="s-card m-badgebox">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem"><h3 class="m-h" style="margin:0">Rozetler</h3><a href="{{ route('student.badges') }}" class="s-chip">Hepsi</a></div>
    @if($nextBadge)<p class="m-next"><b>Sıradaki hedef: {{ $nextBadge->emoji }} {{ $nextBadge->name }}</b><span>{{ max(0, $nextBadge->xp_threshold - $lv['xp']) }} XP kaldı</span></p>@endif
    <div class="m-badges">@foreach($badges as $b)@php($has = in_array($b->id, $earned))<span title="{{ $b->name }} — {{ $b->description }}" class="{{ $has ? 'on' : '' }}">{{ $has ? $b->emoji : '🔒' }}</span>@endforeach</div>
  </section>

  @foreach($announcements as $x)
    <section class="s-card m-note"><b>📢 {{ $x->title }}</b><span>{{ \Illuminate\Support\Str::limit($x->content, 200) }}</span></section>
  @endforeach
</div>
