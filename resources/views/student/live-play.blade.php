@extends('layouts.app')
@section('title', 'Canlı')
@section('content')
<div class="max-w-2xl">
<div class="flex justify-between mb-3"><div class="text-slate-500">{{ \App\Models\LiveSession::TYPES[$session->type] }} · {{ $session->join_code }}</div><div id="timer" class="font-mono text-xl"></div></div>
<div id="stage" class="bg-white rounded-xl shadow p-6 min-h-48"></div>
<table class="w-full mt-4 bg-white rounded-xl shadow text-sm"><tbody id="board"></tbody></table>
</div>
<script>
const TYPE = @json($session->type), STATUS_URL = @json(route('student.live.status', $session)), ANSWER_URL = @json(route('student.live.answer', $session)), TYPING_URL = @json(route('student.live.typing', $session)), CSRF = @json(csrf_token());
const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const post = (u, b) => fetch(u, {method:'POST', headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':CSRF}, body:JSON.stringify(b)}).then(r => r.json());
let offset = 0, endsAt = null, last = null, shownQ = -1, typingBuilt = false, lastSent = '';
const stage = document.getElementById('stage');

function render(j) {
  last = j; offset = j.now - Date.now(); endsAt = j.ends_at;
  document.getElementById('board').innerHTML = j.board.map((p,i)=>`<tr class="border-b"><td class="p-2">${i+1}</td><td>${esc(p.name)}</td><td class="text-right p-2">${TYPE==='quiz'?p.score+' puan':p.progress+'%'}</td></tr>`).join('');
  if (j.status === 'lobby') { stage.innerHTML = `<div class="text-center text-slate-500 text-lg">Öğretmenin başlatması bekleniyor… (${j.count} katılımcı)</div>`; shownQ = -1; typingBuilt = false; return; }
  if (j.status === 'finished') { stage.innerHTML = `<div class="text-center"><div class="text-4xl mb-2">🏁</div><div class="text-xl font-semibold">Bitti! Sıran: ${j.me.rank ?? '-'}</div><div class="text-slate-600">Kazanılan XP: ${j.me.xp}</div></div>`; return; }
  if (TYPE === 'quiz') return renderQuiz(j);
  if (TYPE === 'typing') return renderTyping(j);
  renderCompetition(j);
}

function renderQuiz(j) {
  const closed = j.now > j.ends_at, mine = j.my_answer;
  const key = j.index + ':' + (mine ? mine.answer_index : 'x') + ':' + (closed ? 'c' : 'o');
  if (key === shownQ) return; shownQ = key;
  stage.innerHTML = `<div class="text-sm text-slate-500">Soru ${j.index+1} / ${j.total}${j.question.double?' · 2x XP 🔥':''}</div><div class="text-xl font-semibold my-3">${esc(j.question.text)}</div>` +
    j.question.options.map((o,i) => {
      let cls = 'bg-white hover:bg-indigo-50';
      if (closed && j.correct !== undefined) cls = i === j.correct ? 'bg-green-200' : (mine && mine.answer_index === i ? 'bg-red-200' : 'bg-white');
      else if (mine && mine.answer_index === i) cls = 'bg-indigo-200';
      return `<button data-i="${i}" class="opt block w-full text-left border rounded px-4 py-3 mb-2 ${cls}" ${mine||closed?'disabled':''}>${'ABCDEFGHIJ'[i]}) ${esc(o)}</button>`; }).join('') +
    (mine ? `<div class="mt-2 text-slate-600">${closed ? (mine.is_correct ? '✅ Doğru! +'+mine.points : '❌ Yanlış') : 'Yanıtın kaydedildi, süre bitince sonuç görünür.'}</div>` : (closed ? '<div class="mt-2 text-slate-500">Süre doldu.</div>' : ''));
  stage.querySelectorAll('.opt').forEach(b => b.onclick = async () => { await post(ANSWER_URL, {question: j.index, answer: +b.dataset.i}); shownQ = -1; poll(); });
}

function renderCompetition(j) {
  const c = j.config || {};
  stage.innerHTML = `<div class="text-lg font-semibold mb-2">${esc(c.game_name)} — seviye ${c.level_from}–${c.level_to}</div>
    <div class="w-full bg-slate-200 rounded h-4 mb-3"><div class="bg-green-500 h-4 rounded" style="width:${j.me.progress}%"></div></div>
    <a target="_blank" class="inline-block bg-indigo-600 text-white px-4 py-2 rounded" href="/oyun/${esc(c.game_slug)}">Oyunu aç</a>
    <p class="text-sm text-slate-500 mt-2">Seviyeleri bitirdikçe ilerlemen otomatik güncellenir.${j.me.done ? ' ✅ Tamamladın!' : ''}</p>`;
}

function renderTyping(j) {
  const text = (j.config && j.config.text) || '';
  if (!typingBuilt) { typingBuilt = true;
    stage.innerHTML = `<div id="tx" class="text-2xl font-mono leading-relaxed mb-4"></div><input id="inp" autocomplete="off" autocapitalize="off" spellcheck="false" class="w-full border rounded px-3 py-2 font-mono text-lg" placeholder="Yaz…"><div id="st" class="mt-2 text-slate-600"></div>`;
    const inp = document.getElementById('inp'); inp.focus();
    const paint = () => { const v = inp.value; document.getElementById('tx').innerHTML = [...text].map((ch,i)=>`<span class="${i>=v.length?'text-slate-400':(v[i]===ch?'text-green-600':'text-red-600 bg-red-100')}">${ch===' '?'&nbsp;':esc(ch)}</span>`).join(''); };
    inp.oninput = () => { paint(); post(TYPING_URL, {typed: inp.value}); }; paint(); }
  document.getElementById('st').textContent = `İlerleme %${j.me.progress} · ${j.me.wpm} KDK · doğruluk %${j.me.accuracy}` + (j.me.done ? ' ✅ Bitirdin!' : '');
  if (j.me.done) document.getElementById('inp').disabled = true;
}

async function poll() { render(await (await fetch(STATUS_URL, {headers:{Accept:'application/json'}})).json()); }
setInterval(() => { const t = document.getElementById('timer'); t.textContent = endsAt && last && last.status === 'live' ? Math.max(0, Math.round((endsAt - (Date.now()+offset))/1000)) + ' sn' : ''; }, 250);
poll(); setInterval(poll, 1200);
</script>
@endsection
