@extends('layouts.app')
@section('title', $name)
@section('content')
<h1 class="text-2xl font-bold mb-1">{{ $cfg['emoji'] }} {{ $name }}</h1>
<p class="text-slate-500 mb-3">Robotu programla ve bütün ışıkları yak. Tekrar eden işleri <b>Prosedür</b> ile kısalt.</p>
<div class="flex flex-wrap gap-2 mb-4" id="tabs"></div>
<div class="flex gap-8 flex-wrap">
  <canvas id="c" class="bg-white rounded-xl shadow"></canvas>
  <div class="w-80">
    <div class="flex flex-wrap gap-2 mb-3" id="pal"></div>
    <div class="mb-2"><button id="tm" class="font-semibold">▶ Ana program <span id="cm" class="text-slate-400 text-sm"></span></button><div id="lm" class="min-h-10 bg-white rounded p-2 flex flex-wrap gap-1"></div></div>
    <div class="mb-3" id="pw"><button id="tp" class="font-semibold">▶ Prosedür <span id="cp" class="text-slate-400 text-sm"></span></button><div id="lp" class="min-h-10 bg-white rounded p-2 flex flex-wrap gap-1"></div></div>
    <div class="flex gap-2"><button id="run" class="bg-green-600 text-white px-4 py-2 rounded">Çalıştır</button><button id="clr" class="border px-4 py-2 rounded bg-white">Temizle</button></div>
    <div id="msg" class="mt-3"></div>
    <p class="text-xs text-slate-400 mt-2">Komutlara tıklayarak ekle; programdaki bir komuta tıklayarak sil. Prosedür içinde P kullanılamaz.</p>
  </div>
</div>
@include('games.partials.common')
<script>
const LEVELS = @json($levels), IC = {F:'⬆ İleri', L:'↶ Sol', R:'↷ Sağ', X:'💡 Yak', P:'📦 Prosedür'}, DIRS = [[1,0],[0,1],[-1,0],[0,-1]], ARROW = ['➡️','⬇️','⬅️','⬆️'];
const $ = id => document.getElementById(id), cv = $('c'), g = cv.getContext('2d'), S = 60;
let main = [], proc = [], target = 'main', busy = false, t0 = Date.now(), lit = new Set();

function parse() { const L = LEVELS[GAME.level]; let s = [0, 0], lights = [];
  L.grid.forEach((row, y) => [...row].forEach((ch, x) => { if (ch === 'S') s = [x, y]; if (ch === 'L') lights.push(x + ',' + y); })); return {L, s, lights}; }
function draw(p, litSet) { const {L, lights} = parse(); const w = L.grid[0].length, h = L.grid.length; cv.width = w * S; cv.height = h * S; g.clearRect(0, 0, cv.width, cv.height);
  g.font = '32px serif'; g.textAlign = 'center'; g.textBaseline = 'middle';
  L.grid.forEach((row, y) => [...row].forEach((ch, x) => { g.fillStyle = ch === '#' ? '#334155' : '#f1f5f9'; g.fillRect(x * S + 1, y * S + 1, S - 2, S - 2);
    if (lights.includes(x + ',' + y)) g.fillText(litSet.has(x + ',' + y) ? '💡' : '⚪', x * S + 30, y * S + 32); }));
  g.fillText(ARROW[p.d], p.x * S + 30, p.y * S + 32); }
function lists() {
  const L = LEVELS[GAME.level];
  const fill = (el, arr, name) => { el.innerHTML = arr.map((c, i) => `<button data-i="${i}" class="px-2 py-1 bg-indigo-100 rounded text-sm">${IC[c]}</button>`).join(''); el.querySelectorAll('button').forEach(b => b.onclick = () => { if (!busy) { arr.splice(+b.dataset.i, 1); lists(); } }); };
  fill($('lm'), main); fill($('lp'), proc);
  $('cm').textContent = `${main.length}/${L.main_max}`; $('cp').textContent = `${proc.length}/${L.proc_max}`;
  $('pw').style.display = L.proc_max > 0 ? '' : 'none';
  $('lm').style.outline = target === 'main' ? '2px solid #2447f5' : ''; $('lp').style.outline = target === 'proc' ? '2px solid #2447f5' : '';
  $('pal').innerHTML = ['F','L','R','X'].concat(L.proc_max > 0 && target === 'main' ? ['P'] : []).map(c => `<button data-c="${c}" class="px-3 py-1 bg-indigo-600 text-white rounded">${IC[c]}</button>`).join('');
  $('pal').querySelectorAll('button').forEach(b => b.onclick = () => { if (busy) return; const arr = target === 'main' ? main : proc, max = target === 'main' ? L.main_max : L.proc_max; if (arr.length < max) { arr.push(b.dataset.c); lists(); } });
}
$('tm').onclick = () => { target = 'main'; lists(); }; $('tp').onclick = () => { target = 'proc'; lists(); };
function reset() { main = []; proc = []; target = 'main'; busy = false; lit = new Set(); t0 = Date.now(); $('msg').innerHTML = ''; const {s} = parse(); draw({x: s[0], y: s[1], d: 0}, lit); lists(); GAME.tabs($('tabs'), LEVELS.length, reset); }
$('clr').onclick = () => { if (!busy) reset(); };
const say = (cls, html) => $('msg').innerHTML = `<div class="p-3 rounded ${cls}">${html}</div>`;

$('run').onclick = async () => {
  if (busy || !main.length) return; busy = true; lit = new Set();
  const {L, s, lights} = parse(); const w = L.grid[0].length, h = L.grid.length; let p = {x: s[0], y: s[1], d: 0}; draw(p, lit);
  const flat = []; main.forEach(c => c === 'P' ? flat.push(...proc) : flat.push(c));
  for (const c of flat) { await new Promise(r => setTimeout(r, 300));
    if (c === 'L') p.d = (p.d + 3) % 4; else if (c === 'R') p.d = (p.d + 1) % 4;
    else if (c === 'X') { const k = p.x + ',' + p.y; if (lights.includes(k)) lit.add(k); }
    else { const nx = p.x + DIRS[p.d][0], ny = p.y + DIRS[p.d][1];
      if (nx < 0 || ny < 0 || nx >= w || ny >= h || L.grid[ny][nx] === '#') { say('bg-red-100 text-red-800', '💥 Robot duvara çarptı.'); busy = false; return; } p.x = nx; p.y = ny; }
    draw(p, lit); }
  if (lit.size !== lights.length) { say('bg-amber-100 text-amber-800', `Bütün ışıklar yanmadı (${lit.size}/${lights.length}).`); busy = false; return; }
  const j = await GAME.submit(GAME.level + 1, Math.round((Date.now() - t0) / 1000), {main, proc});
  if (j.ok) { say('bg-green-100 text-green-800', '🎉 Seviye tamam!' + (j.repeat === false ? ' +' + j.xp + ' XP' : '')); GAME.tabs($('tabs'), LEVELS.length, reset); }
  else say('bg-red-100 text-red-800', (j.messages || []).map(GAME.esc).join('<br>'));
  busy = false;
};
reset();
</script>
@endsection
