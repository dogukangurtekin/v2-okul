@extends('layouts.app')
@section('title', $name)
@section('content')
<h1 class="text-2xl font-bold mb-3">{{ $cfg['emoji'] }} {{ $name }}</h1>
<div class="flex gap-2 mb-3" id="lv"></div>
<div class="flex gap-8">
  <canvas id="c" width="300" height="300" class="bg-white rounded-xl shadow"></canvas>
  <div class="w-72">
    <div class="flex gap-2 mb-2">
      <button data-cmd="ileri" class="px-3 py-1 bg-indigo-600 text-white rounded">⬆ İleri</button>
      <button data-cmd="sol" class="px-3 py-1 bg-indigo-600 text-white rounded">↶ Sol</button>
      <button data-cmd="sag" class="px-3 py-1 bg-indigo-600 text-white rounded">↷ Sağ</button>
    </div>
    <div id="prog" class="min-h-16 bg-white rounded p-2 mb-2 flex flex-wrap gap-1 text-sm"></div>
    <div class="text-sm text-slate-500 mb-2">Komut: <span id="cnt">0</span> / <span id="max"></span></div>
    <button id="run" class="px-4 py-2 bg-green-600 text-white rounded">Çalıştır</button>
    <button id="clear" class="px-4 py-2 border rounded">Temizle</button>
    <div id="msg" class="mt-3 font-medium"></div>
  </div>
</div>
<script>
const LEVELS = @json($levels), DONE = new Set(@json($done)), CAN = @json($canRecord);
const ICON = {ileri:'⬆', sol:'↶', sag:'↷'}, DIRS = [[1,0],[0,1],[-1,0],[0,-1]]; // sag, asagi, sol, yukari
let lvl = 0, prog = [], busy = false, t0 = Date.now();
const c = document.getElementById('c'), g = c.getContext('2d'), N = 5, S = 60;
function parse() { const m = LEVELS[lvl].grid; let s, goal; m.forEach((r, y) => [...r].forEach((ch, x) => { if (ch==='S') s={x,y}; if (ch==='G') goal={x,y}; })); return {m, s, goal}; }
function draw(p) {
  const {m, goal} = parse(); g.clearRect(0,0,300,300);
  m.forEach((r,y)=>[...r].forEach((ch,x)=>{ g.fillStyle = ch==='#' ? '#334155' : '#f1f5f9'; g.fillRect(x*S+1,y*S+1,S-2,S-2); }));
  g.font='36px serif'; g.textAlign='center'; g.textBaseline='middle';
  g.fillText('🏁', goal.x*S+30, goal.y*S+32);
  g.fillText(['➡️','⬇️','⬅️','⬆️'][p.d], p.x*S+30, p.y*S+32);
}
function tabs() { const el = document.getElementById('lv'); el.innerHTML='';
  LEVELS.forEach((_,i)=>{ const b=document.createElement('button'); const open = i===0 || DONE.has(i);
    b.textContent=(DONE.has(i+1)?'✅ ':'')+(i+1); b.disabled=!open && !DONE.has(i+1);
    b.className='px-3 py-1 rounded border '+(i===lvl?'bg-indigo-600 text-white':'bg-white')+(b.disabled?' opacity-40':'');
    b.onclick=()=>{lvl=i; reset();}; el.appendChild(b); }); }
function reset() { prog=[]; busy=false; t0=Date.now(); document.getElementById('msg').textContent=''; document.getElementById('max').textContent=LEVELS[lvl].max; render(); const {s}=parse(); draw({...s,d:0}); tabs(); }
function render() { document.getElementById('prog').innerHTML = prog.map(p=>`<span class="px-2 bg-indigo-100 rounded">${ICON[p]}</span>`).join(''); document.getElementById('cnt').textContent = prog.length; }
document.querySelectorAll('[data-cmd]').forEach(b=>b.onclick=()=>{ if(!busy && prog.length<LEVELS[lvl].max){prog.push(b.dataset.cmd); render();} });
document.getElementById('clear').onclick=()=>{ if(!busy){prog=[]; render(); const {s}=parse(); draw({...s,d:0});} };
document.getElementById('run').onclick=async()=>{
  if (busy||!prog.length) return; busy=true; const {m,s,goal}=parse(); let p={...s,d:0}; draw(p); const msg=document.getElementById('msg');
  for (const cmd of prog) { await new Promise(r=>setTimeout(r,350));
    if (cmd==='sol') p.d=(p.d+3)%4; else if (cmd==='sag') p.d=(p.d+1)%4;
    else { const nx=p.x+DIRS[p.d][0], ny=p.y+DIRS[p.d][1];
      if (nx<0||ny<0||nx>=N||ny>=N||m[ny][nx]==='#') { msg.textContent='💥 Duvara çarptın, tekrar dene.'; busy=false; return; } p.x=nx; p.y=ny; }
    draw(p); }
  if (p.x===goal.x && p.y===goal.y) {
    msg.textContent='🎉 Tebrikler!';
    if (CAN) { const r = await fetch(@json(route('games.result', $slug)), {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())}, body: JSON.stringify({level:lvl+1, seconds:Math.round((Date.now()-t0)/1000), score:Math.max(0,LEVELS[lvl].max-prog.length)*10})});
      if (r.ok) { const j = await r.json(); DONE.add(lvl+1); msg.textContent += j.repeat ? '' : ` +${j.xp} XP`; tabs(); } }
  } else msg.textContent='Hedefe ulaşamadın, tekrar dene.';
  busy=false;
};
reset();
</script>
@endsection
