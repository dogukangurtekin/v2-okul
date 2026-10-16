@php($first = explode(' ', trim($student->user->name))[0])
<div class="rise t-dash">
  <header class="t-top">
    <div><h1>Merhaba, {{ $first }}</h1><p>{{ $student->schoolClass?->label }} · {{ now()->translatedFormat('d F, l') }}</p></div>
    @if($live)<a href="{{ route('student.live') }}" class="t-live"><span class="dot"></span>Canlı oturum açık · katıl</a>@endif
  </header>

  <section class="t-read">
    <div><small>seviye</small><b>{{ $lv['level'] }}</b><span>{{ $lv['title'] }}</span></div>
    <div><small>toplam xp</small><b data-count="{{ $lv['xp'] }}">{{ $lv['xp'] }}</b><span>{{ $lv['remaining'] }} sonraki seviyeye</span></div>
    <div><small>sınıf sırası</small><b>{{ $rank ? '#'.$rank : '–' }}</b><span>{{ $classSize > 1 ? $classSize.' öğrenci' : 'sınıf' }}</span></div>
    <div><small>harcanabilir</small><b>{{ $student->xp_balance }}</b><span>avatar mağazası</span></div>
    <div class="t-bar"><i data-pct="{{ $lv['pct'] }}" style="width:{{ $lv['pct'] }}%"></i></div>
  </section>

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
      @if($resume)
      <section class="s-card t-panel">
        <h3>Kaldığın yer</h3>
        <a href="{{ route('student.courses.show', $resume['course']) }}" class="t-resume"><b>{{ $resume['course']->name }}</b>
          <div class="t-line"><i data-pct="{{ round($resume['at'] / $resume['total'] * 100) }}" style="width:0"></i></div><small>{{ $resume['at'] }} / {{ $resume['total'] }} slayt</small></a>
      </section>
      @endif
      @foreach($announcements as $x)
        <section class="s-card t-panel"><h3>Duyuru</h3><b>{{ $x->title }}</b><p class="t-none" style="text-align:left;margin:.3rem 0 0">{{ \Illuminate\Support\Str::limit($x->content, 200) }}</p></section>
      @endforeach
    </div>
    <aside style="display:grid;gap:1rem;align-content:start">
      <section class="s-card t-panel">
        <h3>İlerleme</h3>
        <div class="t-kv"><span>Dersler</span><b>{{ $stats['courses_done'] }}/{{ $stats['courses_total'] }}</b></div>
        <div class="t-kv"><span>Açık oyun</span><b>{{ $stats['games'] }}</b></div>
        <div class="t-kv"><span>Rozet</span><b>{{ count($earned) }}/{{ $badges->count() }}</b></div>
      </section>
      <section class="s-card t-panel t-links">
        <h3>Git</h3>
        <a href="{{ route('student.courses') }}"><x-icon name="book" />Derslerim</a>
        <a href="{{ route('games.index') }}"><x-icon name="gamepad" />Oyunlar</a>
        <a href="{{ route('student.ranking') }}"><x-icon name="trophy" />Sıralama</a>
        <a href="{{ route('student.badges') }}"><x-icon name="medal" />Rozetler</a>
        <a href="{{ route('student.avatars') }}"><x-icon name="star" />Avatar mağazası</a>
      </section>
    </aside>
  </div>
</div>
