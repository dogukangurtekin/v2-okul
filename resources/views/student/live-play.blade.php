@extends('layouts.app')
@section('title', 'Canlı')
@section('content')
<div style="max-width:720px;margin:0 auto">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.9rem;gap:.6rem">
  <span class="s-chip" style="font-size:.85rem">{{ ['quiz' => '❓', 'competition' => '🏁', 'typing' => '⌨️'][$session->type] ?? '⚡' }} {{ \App\Models\LiveSession::TYPES[$session->type] }} · {{ $session->join_code }}</span>
  <span id="timer" class="timer" style="visibility:hidden">0</span>
</div>
<div id="stage" class="s-card" style="min-height:14rem;padding:1.5rem"></div>
<div class="s-card" style="margin-top:1rem;padding:.8rem 1rem"><div class="s-title" style="font-size:1rem;margin-bottom:.3rem">🏆 Sıralama</div><div id="board"></div></div>
</div>
<script>
const TYPE = @json($session->type), STATUS_URL = @json(route('student.live.status', $session)), ANSWER_URL = @json(route('student.live.answer', $session)), TYPING_URL = @json(route('student.live.typing', $session)), CSRF = @json(csrf_token());
const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const post = (u, b) => fetch(u, {method:'POST', headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':CSRF}, body:JSON.stringify(b)}).then(r => r.json());
const SHAPES = ['▲', '◆', '●', '■', '★', '♥'];
let offset = 0, endsAt = null, last = null, shownQ = -1, typingBuilt = false, celebrated = false;
const stage = document.getElementById('stage');

function render(j) {
  last = j; offset = j.now - Date.now(); endsAt = j.ends_at;
  document.getElementById('board').innerHTML = j.board.map((p, i) => `<div style="display:flex;align-items:center;gap:.6rem;padding:.35rem 0;border-top:${i ? '2px dashed #f3e8ff' : '0'}"><b style="width:1.6rem;color:#7c3aed">${['🥇','🥈','🥉'][i] || (i + 1)}</b><span style="flex:1;font-weight:800">${esc(p.name)}</span><span class="s-chip">${TYPE === 'quiz' ? p.score + ' puan' : p.progress + '%'}</span></div>`).join('') || '<div style="color:#9ca3af;font-weight:700;padding:.4rem 0">Henüz kimse yok</div>';
  if (j.status === 'lobby') { stage.innerHTML = `<div style="text-align:center;padding:1.5rem 0"><div style="font-size:4rem;animation:bob 2.4s ease-in-out infinite">⏳</div><div class="s-title" style="font-size:1.4rem;margin-top:.5rem">Öğretmenin başlatması bekleniyor…</div><div class="s-chip" style="margin-top:.8rem">${j.count} katılımcı hazır</div></div>`; shownQ = -1; typingBuilt = false; return; }
  if (j.status === 'finished') {
    const me = j.me || {};
    stage.innerHTML = `<div style="text-align:center;padding:1rem 0"><div style="font-size:4.4rem;animation:pop .6s both">${me.rank === 1 ? '🏆' : (me.rank && me.rank <= 3 ? '🥳' : '🏁')}</div><div class="s-title" style="font-size:1.7rem;margin:.3rem 0">Bitti!</div><div class="s-chip" style="font-size:1rem">Sıran: ${me.rank ?? '-'}</div> <div class="s-chip warn" style="font-size:1rem">🪙 +${me.xp ?? 0} XP</div></div>`;
    if (!celebrated) { celebrated = true; if (window.UI) UI.celebrate({xp: me.xp || 0}); }
    return;
  }
  if (TYPE === 'quiz') return renderQuiz(j);
  if (TYPE === 'typing') return renderTyping(j);
  renderCompetition(j);
}

function renderQuiz(j) {
  const closed = j.now > j.ends_at, mine = j.my_answer;
  const key = j.index + ':' + (mine ? mine.answer_index : 'x') + ':' + (closed ? 'c' : 'o');
  if (key === shownQ) return; shownQ = key;
  stage.innerHTML = `<div style="display:flex;justify-content:space-between;align-items:center"><span class="s-chip">Soru ${j.index + 1} / ${j.total}</span>${j.question.double ? '<span class="s-chip pink">🔥 2x XP</span>' : ''}</div><div class="s-title" style="font-size:1.45rem;margin:1rem 0 1.1rem;line-height:1.3">${esc(j.question.text)}</div>` +
    j.question.options.map((o, i) => {
      let st = '';
      if (closed && j.correct !== undefined) st = i === j.correct ? ' right' : ' dim';
      else if (mine && mine.answer_index === i) st = ' picked';
      else if (mine) st = ' dim';
      return `<button data-i="${i}" class="opt q-opt c${i % 6}${st}" ${mine || closed ? 'disabled' : ''}><i>${SHAPES[i % 6]}</i><span>${esc(o)}</span></button>`; }).join('') +
    (mine ? `<div style="margin-top:.6rem;font-weight:900;font-size:1.05rem;color:${closed ? (mine.is_correct ? '#15803d' : '#b91c1c') : '#6d28d9'}">${closed ? (mine.is_correct ? '✅ Doğru! +' + mine.points + ' puan' : '❌ Yanlış, bir dahaki sefere!') : '👍 Yanıtın kaydedildi, süre bitince sonuç görünür.'}</div>` : (closed ? '<div style="margin-top:.6rem;font-weight:900;color:#6b7280">⏰ Süre doldu.</div>' : ''));
  stage.querySelectorAll('.opt').forEach(b => b.onclick = async () => { await post(ANSWER_URL, {question: j.index, answer: +b.dataset.i}); shownQ = -1; poll(); });
  if (closed && mine && mine.is_correct && window.UI && !stage.dataset['c' + j.index]) { stage.dataset['c' + j.index] = '1'; UI.celebrate({}); }
}

function renderCompetition(j) {
  const c = j.config || {};
  stage.innerHTML = `<div class="s-title" style="font-size:1.3rem">${esc(c.game_name)} <span class="s-chip">seviye ${c.level_from}–${c.level_to}</span></div>
    <div class="xpbar" style="margin:1rem 0"><i style="width:${j.me.progress}%"></i></div>
    <a target="_blank" class="s-btn green" href="/oyun/${esc(c.game_slug)}">🎮 Oyunu aç</a>
    <p style="margin:.9rem 0 0;font-weight:700;color:#6d28d9">Seviyeleri bitirdikçe ilerlemen otomatik güncellenir.${j.me.done ? ' ✅ Tamamladın!' : ''}</p>`;
}

function renderTyping(j) {
  const text = (j.config && j.config.text) || '';
  if (!typingBuilt) { typingBuilt = true;
    stage.innerHTML = `<div id="tx" style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:1.5rem;line-height:1.7;margin-bottom:1rem"></div><input id="inp" autocomplete="off" autocapitalize="off" spellcheck="false" style="width:100%;border:3px solid #ddd6fe;border-radius:1rem;padding:.8rem 1rem;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:1.2rem;outline:none" placeholder="Buraya yaz…"><div id="st" style="margin-top:.7rem;font-weight:800;color:#6d28d9"></div>`;
    const inp = document.getElementById('inp'); inp.focus();
    const paint = () => { const v = inp.value; document.getElementById('tx').innerHTML = [...text].map((ch, i) => `<span style="${i >= v.length ? 'color:#a1a1aa' : (v[i] === ch ? 'color:#16a34a;font-weight:800' : 'color:#dc2626;background:#fee2e2;border-radius:4px')}">${ch === ' ' ? '&nbsp;' : esc(ch)}</span>`).join(''); };
    inp.oninput = () => { paint(); post(TYPING_URL, {typed: inp.value}); }; paint(); }
  document.getElementById('st').textContent = `İlerleme %${j.me.progress} · ${j.me.wpm} KDK · doğruluk %${j.me.accuracy}` + (j.me.done ? ' ✅ Bitirdin!' : '');
  if (j.me.done) document.getElementById('inp').disabled = true;
}

async function poll() { render(await (await fetch(STATUS_URL, {headers:{Accept:'application/json'}})).json()); }
setInterval(() => {
  const t = document.getElementById('timer'), live = endsAt && last && last.status === 'live';
  const left = live ? Math.max(0, Math.round((endsAt - (Date.now() + offset)) / 1000)) : 0;
  t.style.visibility = live ? 'visible' : 'hidden'; t.textContent = left + ' sn'; t.classList.toggle('low', live && left <= 5);
}, 250);
poll(); setInterval(poll, 1200);
</script>
@endsection
