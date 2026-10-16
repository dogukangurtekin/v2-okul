@extends('layouts.app')
@section('title', $name)
@section('content')
<x-page-head :title="$cfg['emoji'].' '.$name" :back="[route('games.index'), 'Etkinlikler']">
  @if($classic ?? false)@if(view()->exists('games.native.'.$slug))<a href="{{ route('games.play', $slug) }}?v2=1" class="btn btn-ghost btn-sm">Yeni sürüm (sunucu doğrulamalı)</a>@endif @endif
</x-page-head>
@php($role = auth()->user()->hasRole('admin') ? 'admin' : (auth()->user()->hasRole('teacher') ? 'teacher' : 'student'))
<iframe src="{{ asset('runner-assets/'.$slug.'/index.html') }}?role={{ $role }}" class="card" style="width:100%;height:78vh;border:0;background:#fff" allow="fullscreen"></iframe>
@if($canRecord)
<script>
// Paket oyunlar sonucu window.parent.postMessage ile bildirir; v1 mesaj biçimlerini v2 sonuç ucuna çevirir.
let lastIdx = 0;
window.addEventListener('message', e => {
  if (e.origin !== location.origin || !e.data || typeof e.data !== 'object') return;
  const d = e.data;
  if (d.type === 'GAME_UPDATE' && Number.isFinite(+d.currentLevelIndex)) lastIdx = +d.currentLevelIndex;
  let level = 0, seconds = 0;
  if (d.type === 'LEVEL_COMPLETED') {
    level = +d.levelNo
      || (d.source === 'connect-the-dots' ? lastIdx + 1 : 0)
      || (Number.isFinite(+d.currentLevelIndex) && d.currentLevelIndex !== null && d.currentLevelIndex !== '' ? +d.currentLevelIndex + 1 : 0)
      || (Number.isFinite(+d.levelId) && +d.levelId > 0 ? +d.levelId : 0) || lastIdx + 1;
    seconds = d.elapsedSeconds != null ? +d.elapsedSeconds : (d.duration != null ? +d.duration / 1000 : 0);
  } else if (d.type === 'LINE_TRACE_LEVEL_COMPLETE') {
    level = +d.level; seconds = +d.seconds;
  } else return;
  if (!(level >= 1)) return;
  fetch(@json(route('games.result', $slug)), {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())},
    body: JSON.stringify({classic: true, level: Math.round(level), seconds: Math.max(0, Math.round(seconds || 0)), score: Math.max(0, Math.round(d.xp || d.stars || 0))})});
});
</script>
@endif
@endsection
