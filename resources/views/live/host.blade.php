@extends('layouts.app')
@section('title', 'Oturum '.$session->join_code)
@section('content')
<x-page-head :title="'Oturum '.$session->join_code" :back="[route('live.index'), 'Oturumlar']" />
<div class="card card-pad" style="display:flex;align-items:center;gap:2rem;flex-wrap:wrap;margin-bottom:1rem;background:#fff">
  <div><div class="help">Katılım kodu</div><div style="font-size:3.2rem;line-height:1;font-weight:900;letter-spacing:.18em;font-family:ui-monospace,monospace;color:var(--brand-d)">{{ $session->join_code }}</div></div>
  <div style="color:var(--muted);font-size:.92rem;line-height:1.7"><b style="color:var(--ink)">{{ \App\Models\LiveSession::TYPES[$session->type] }}</b>@if($session->quiz) · {{ $session->quiz->title }}@endif<br><b id="count" style="color:var(--ink)">0</b> katılımcı · <span id="state" class="badge badge-ok"></span> · <b id="timer" style="color:var(--ink)"></b></div>
</div>
<div class="page-actions" style="margin-bottom:1rem">
  @if($session->status === 'lobby')
    <form method="POST" action="{{ route('live.lock', $session) }}">@csrf<button class="btn btn-ghost">🔒 Kilitle / aç</button></form>
    <form method="POST" action="{{ route('live.start', $session) }}">@csrf<button class="btn btn-primary">▶ Başlat</button></form>
  @endif
  @if($session->type === 'quiz' && $session->status === 'live')
    <form method="POST" action="{{ route('live.next', $session) }}">@csrf<button class="btn btn-primary">Sonraki soru / bitir</button></form>
  @endif
  <form method="POST" action="{{ route('live.finish', $session) }}" onsubmit="return confirm('Oturum bitirilsin ve XP dağıtılsın mı?')">@csrf<button class="btn btn-danger">■ Bitir</button></form>
</div>
<div id="q" class="card card-pad hidden" style="margin-bottom:1rem"></div>
<div class="card tbl-wrap" style="max-width:640px"><table class="tbl"><tbody id="board"></tbody></table></div>
<script>
const URL_ = @json(route('live.status', $session)), TYPE = @json($session->type);
let offset = 0, endsAt = null;
async function poll() {
  const j = await (await fetch(URL_, {headers:{Accept:'application/json'}})).json();
  offset = j.now - Date.now(); endsAt = j.ends_at;
  document.getElementById('count').textContent = j.count;
  document.getElementById('state').textContent = {lobby:'Bekleme', live:'Canlı', finished:'Bitti'}[j.status];
  const q = document.getElementById('q');
  if (j.question) { q.classList.remove('hidden'); q.innerHTML = `<div class="help">Soru ${j.index+1} / ${j.total} · ${j.answered} yanıt</div><div style="font-size:1.2rem;font-weight:700;margin:.5rem 0">${esc(j.question.text)}</div>` + j.question.options.map((o,i)=>`<div class="${i===j.correct?'badge badge-ok':''}">${'ABCDEFGHIJ'[i]}) ${esc(o)}</div>`).join(''); } else q.classList.add('hidden');
  document.getElementById('board').innerHTML = j.board.map((p,i)=>`<tr><td style="width:40px;font-weight:800">${i+1}</td><td>${esc(p.name)}</td><td style="text-align:right;font-weight:700">${TYPE==='quiz' ? p.score+' puan' : p.progress+'% · '+p.wpm+' KDK'}</td></tr>`).join('');
  if (j.status === 'finished') location.href = @json(route('live.report', $session));
}
const esc = s => String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
setInterval(() => { const t = document.getElementById('timer'); t.textContent = endsAt ? Math.max(0, Math.round((endsAt - (Date.now()+offset))/1000)) + ' sn' : ''; }, 250);
poll(); setInterval(poll, 1500);
</script>
@endsection
