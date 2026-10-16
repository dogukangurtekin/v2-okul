@extends('layouts.app')
@section('title', $name)
@section('content')
<h1 class="text-2xl font-bold mb-3">{{ $cfg['emoji'] }} {{ $name }}</h1>
<iframe src="{{ asset('runner-assets/'.$slug.'/index.html') }}" class="w-full bg-white rounded-xl shadow" style="height:75vh" allow="fullscreen"></iframe>
@if($canRecord)
<script>
// Paket oyunlar sonucu window.parent.postMessage({type:'level-complete', level, seconds, score}) ile bildirir.
window.addEventListener('message', e => {
  if (e.origin !== location.origin || !e.data || e.data.type !== 'level-complete') return;
  fetch(@json(route('games.result', $slug)), {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())},
    body: JSON.stringify({level: e.data.level|0, seconds: e.data.seconds|0, score: e.data.score|0})});
});
</script>
@endif
@endsection
