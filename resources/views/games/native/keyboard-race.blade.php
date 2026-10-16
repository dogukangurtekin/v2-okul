@extends('layouts.app')
@section('title', $name)
@section('content')
<h1 class="text-2xl font-bold mb-3">{{ $cfg['emoji'] }} {{ $name }}</h1>
<div class="flex gap-2 mb-3" id="lv"></div>
<div class="bg-white rounded-xl shadow p-6 max-w-2xl">
  <div id="text" class="text-2xl font-mono mb-4 leading-relaxed"></div>
  <input id="inp" class="w-full border rounded px-3 py-2 font-mono text-lg" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Yazmaya başlayın...">
  <div id="msg" class="mt-3 font-medium"></div>
</div>
<script>
const LEVELS = @json($levels), DONE = new Set(@json($done)), CAN = @json($canRecord);
let lvl = 0, t0 = null, over = false;
const inp = document.getElementById('inp'), box = document.getElementById('text'), msg = document.getElementById('msg');
function tabs() { const el = document.getElementById('lv'); el.innerHTML='';
  LEVELS.forEach((_,i)=>{ const b=document.createElement('button'); const open = i===0 || DONE.has(i);
    b.textContent=(DONE.has(i+1)?'✅ ':'')+(i+1); b.disabled=!open && !DONE.has(i+1);
    b.className='px-3 py-1 rounded border '+(i===lvl?'bg-indigo-600 text-white':'bg-white')+(b.disabled?' opacity-40':'');
    b.onclick=()=>{lvl=i; reset();}; el.appendChild(b); }); }
function paint() { const t = LEVELS[lvl].text, v = inp.value;
  box.innerHTML = [...t].map((ch,i)=>`<span class="${i>=v.length?'text-slate-400':(v[i]===ch?'text-green-600':'text-red-600 bg-red-100')}">${ch===' '?'&nbsp;':ch}</span>`).join(''); }
function reset() { inp.value=''; t0=null; over=false; msg.textContent=''; inp.disabled=false; inp.focus(); paint(); tabs(); }
inp.addEventListener('input', async () => {
  if (over) return; if (!t0) t0 = Date.now(); paint();
  const t = LEVELS[lvl].text, v = inp.value;
  if (v.length < t.length) return;
  over = true; inp.disabled = true;
  let ok = 0; for (let i=0;i<t.length;i++) if (v[i]===t[i]) ok++;
  const secs = Math.max(1,(Date.now()-t0)/1000), acc = Math.round(ok/t.length*100), wpm = Math.round((t.length/5)/(secs/60));
  if (acc < 90) { msg.textContent = `Doğruluk %${acc} — en az %90 gerekli. Tekrar dene!`; setTimeout(reset, 1500); return; }
  msg.textContent = `🎉 ${wpm} KDK · doğruluk %${acc}`;
  if (CAN) { const r = await fetch(@json(route('games.result', $slug)), {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())}, body: JSON.stringify({level:lvl+1, seconds:Math.round(secs), score:wpm})});
    if (r.ok) { const j = await r.json(); DONE.add(lvl+1); msg.textContent += j.repeat ? '' : ` · +${j.xp} XP`; tabs(); } }
});
reset();
</script>
@endsection
