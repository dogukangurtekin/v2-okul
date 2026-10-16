@php($first = explode(' ', trim($student->user->name))[0])
<div class="rise" style="display:grid;gap:1.1rem">
  <section class="m-head">
    <div class="m-who"><div class="m-av">{{ $student->avatar?->emoji ?? '🙂' }}</div>
      <div><h1>Selam {{ $first }}</h1><p>{{ $student->schoolClass?->label }}@if($rank) · sınıfta {{ $rank }}. sıradasın @if($classSize > 1)<span>/ {{ $classSize }}</span>@endif @endif</p></div></div>
    <div class="m-lv"><div class="m-lv-top"><b>Seviye {{ $lv['level'] }} · {{ $lv['title'] }}</b><span><span data-count="{{ $lv['xp'] }}">{{ $lv['xp'] }}</span> XP</span></div>
      <div class="xpbar"><i data-pct="{{ $lv['pct'] }}" style="width:{{ $lv['pct'] }}%"></i></div>
      <small>Sonraki seviyeye {{ $lv['remaining'] }} XP · harcanabilir {{ $student->xp_balance }} XP</small></div>
  </section>

  @if($live)
    <a href="{{ route('student.live') }}" class="m-live"><x-icon name="bolt" /><div><b>Sınıfında canlı bir oturum açık</b><span>Katıl ve XP kazan</span></div><i>Katıl</i></a>
  @endif

  <div class="m-tiles">
    <a href="{{ route('student.courses') }}" class="s-card"><x-icon name="book" /><b>{{ $stats['courses_done'] }}<small>/{{ $stats['courses_total'] }}</small></b><span>Ders tamamlandı</span></a>
    <a href="{{ route('student.assignments') }}" class="s-card"><x-icon name="clipboard" /><b>{{ $pending->count() }}</b><span>Bekleyen ödev</span></a>
    <a href="{{ route('games.index') }}" class="s-card"><x-icon name="gamepad" /><b>{{ $stats['games'] }}</b><span>Açık oyun</span></a>
    <a href="{{ route('student.badges') }}" class="s-card"><x-icon name="medal" /><b>{{ count($earned) }}<small>/{{ $badges->count() }}</small></b><span>Rozet</span></a>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.1rem">
    <section class="s-card">
      <h3 class="m-h">Yapılacak ödevler</h3>
      @forelse($pending->take(5) as $a)
        @php($late = $a->due_date && $a->due_date->isPast() && ! $a->due_date->isToday())
        <a href="{{ route('student.assignments.open', $a) }}" class="m-row"><x-icon :name="$a->type === 'game' ? 'gamepad' : 'book'" />
          <div><b>{{ $a->title }}</b><span class="{{ $late ? 'late' : '' }}">{{ $a->due_date ? ($late ? 'Süresi geçti · ' : 'Son: ').$a->due_date->format('d.m') : 'Süresiz' }}</span></div><em>+{{ $a->xp_reward }} XP</em></a>
      @empty
        <p class="m-none">Bekleyen ödevin yok.</p>
      @endforelse
    </section>
    <section class="s-card">
      <h3 class="m-h">Devam et</h3>
      @if($resume)
        <b style="display:block;font-size:1.05rem">{{ $resume['course']->name }}</b>
        <div class="xpbar" style="margin:.7rem 0 .4rem"><i data-pct="{{ round($resume['at'] / $resume['total'] * 100) }}" style="width:0"></i></div>
        <small style="color:var(--muted)">Slayt {{ $resume['at'] }} / {{ $resume['total'] }}</small>
        <div style="margin-top:.9rem"><a class="s-btn" href="{{ route('student.courses.show', $resume['course']) }}">Derse devam et</a></div>
      @else
        <p class="m-none">Henüz başladığın bir ders yok.</p><a class="s-btn" href="{{ route('student.courses') }}">Derslere git</a>
      @endif
    </section>
  </div>

  <section class="s-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.7rem"><h3 class="m-h" style="margin:0">Rozetler</h3><a href="{{ route('student.badges') }}" class="s-chip">Hepsi</a></div>
    <div class="m-badges">@foreach($badges as $b)@php($has = in_array($b->id, $earned))<span title="{{ $b->name }} — {{ $b->description }}" class="{{ $has ? 'on' : '' }}">{{ $has ? $b->emoji : '🔒' }}</span>@endforeach</div>
  </section>

  @foreach($announcements as $x)
    <section class="s-card m-note"><b>{{ $x->title }}</b><span>{{ \Illuminate\Support\Str::limit($x->content, 200) }}</span></section>
  @endforeach
</div>
