@extends('layouts.app')
@section('title', 'Oturum '.$session->join_code)
@section('content')
<div class="flex items-center gap-6 mb-4">
  <div><div class="text-slate-500 text-sm">Katılım kodu</div><div class="text-5xl font-mono font-bold tracking-widest">{{ $session->join_code }}</div></div>
  <div class="text-slate-600">{{ \App\Models\LiveSession::TYPES[$session->type] }}@if($session->quiz) · {{ $session->quiz->title }}@endif<br><span id="count">0</span> katılımcı · <span id="state"></span> · <span id="timer"></span></div>
</div>
<div class="flex gap-2 mb-4">
  @if($session->status === 'lobby')
    <form method="POST" action="{{ route('live.lock', $session) }}">@csrf<button class="border rounded px-4 py-2 bg-white">Kilitle / aç</button></form>
    <form method="POST" action="{{ route('live.start', $session) }}">@csrf<button class="bg-green-600 text-white rounded px-4 py-2">Başlat</button></form>
  @endif
  @if($session->type === 'quiz' && $session->status === 'live')
    <form method="POST" action="{{ route('live.next', $session) }}">@csrf<button class="bg-indigo-600 text-white rounded px-4 py-2">Sonraki soru / bitir</button></form>
  @endif
  <form method="POST" action="{{ route('live.finish', $session) }}" onsubmit="return confirm('Oturum bitirilsin ve XP dağıtılsın mı?')">@csrf<button class="bg-red-600 text-white rounded px-4 py-2">Bitir</button></form>
</div>
<div id="q" class="bg-white rounded-xl shadow p-5 mb-4 hidden"></div>
<table class="w-full max-w-xl bg-white rounded-xl shadow text-sm"><tbody id="board"></tbody></table>
<script>
const URL_ = @json(route('live.status', $session)), TYPE = @json($session->type);
let offset = 0, endsAt = null;
async function poll() {
  const j = await (await fetch(URL_, {headers:{Accept:'application/json'}})).json();
  offset = j.now - Date.now(); endsAt = j.ends_at;
  document.getElementById('count').textContent = j.count;
  document.getElementById('state').textContent = {lobby:'Bekleme', live:'Canlı', finished:'Bitti'}[j.status];
  const q = document.getElementById('q');
  if (j.question) { q.classList.remove('hidden'); q.innerHTML = `<div class="text-slate-500 text-sm">Soru ${j.index+1} / ${j.total} · ${j.answered} yanıt</div><div class="text-xl font-semibold my-2">${esc(j.question.text)}</div>` + j.question.options.map((o,i)=>`<div class="${i===j.correct?'font-bold text-green-700':''}">${'ABCDEFGHIJ'[i]}) ${esc(o)}</div>`).join(''); } else q.classList.add('hidden');
  document.getElementById('board').innerHTML = j.board.map((p,i)=>`<tr class="border-b"><td class="p-2">${i+1}</td><td>${esc(p.name)}</td><td class="text-right p-2">${TYPE==='quiz' ? p.score+' puan' : p.progress+'% · '+p.wpm+' KDK'}</td></tr>`).join('');
  if (j.status === 'finished') location.href = @json(route('live.report', $session));
}
const esc = s => String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
setInterval(() => { const t = document.getElementById('timer'); t.textContent = endsAt ? Math.max(0, Math.round((endsAt - (Date.now()+offset))/1000)) + ' sn' : ''; }, 250);
poll(); setInterval(poll, 1500);
</script>
@endsection
