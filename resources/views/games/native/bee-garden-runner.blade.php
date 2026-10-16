@extends('layouts.app')
@section('title', $name)
@section('content')
<h1 class="text-2xl font-bold mb-1">{{ $cfg['emoji'] }} {{ $name }}</h1>
<p class="text-slate-500 mb-3">CSS Flexbox ile arıları çiçeklere ulaştır. Yalnızca <code>#garden</code> için kural yazabilirsin. <span class="text-xs">(Önizleme betik çalıştırmaz.)</span></p>
<div class="flex flex-wrap gap-2 mb-4" id="tabs"></div>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4 max-w-4xl"><b id="ttl"></b> — <span id="task"></span></div>
<div class="grid grid-cols-2 gap-4 max-w-4xl">
  <div class="space-y-2">
    <label class="text-sm font-semibold">CSS</label>
    <textarea id="css" rows="10" spellcheck="false" class="w-full border rounded-xl p-3 font-mono text-sm bg-white"></textarea>
    <div class="text-xs text-slate-500">Kullanabileceklerin: <code>display</code>, <code>flex-direction</code>, <code>justify-content</code>, <code>align-items</code></div>
    <button id="chk" class="bg-indigo-600 text-white px-4 py-2 rounded">Kontrol et</button><div id="msg" class="mt-2"></div>
  </div>
  <div><label class="text-sm font-semibold">Bahçe</label>
    <iframe id="prev" sandbox="" class="bg-white border rounded-xl" style="width:330px;height:330px" title="Bahçe önizlemesi"></iframe></div>
</div>
@include('games.partials.common')
<script>
const LEVELS = @json($levels); const $ = id => document.getElementById(id); let t0 = Date.now(), timer = null;
// Önizleme tarayıcının gerçek Flexbox'ını kullanır; kutu/arı boyutları sunucudaki modelle aynı olacak şekilde !important ile sabitlenir.
const BASE = 'body{margin:10px;font-family:sans-serif}.wrap{position:relative;width:300px;height:300px;background:#bbf7d0;outline:2px solid #16a34a}.flower{position:absolute;width:60px;height:60px;font-size:40px;line-height:60px;text-align:center;opacity:.55}';
const FORCED = '#garden{position:absolute!important;left:0!important;top:0!important;width:300px!important;height:300px!important;box-sizing:border-box!important;padding:0!important;margin:0!important;border:0!important;gap:0!important;row-gap:0!important;column-gap:0!important;flex-wrap:nowrap!important;transform:none!important;overflow:visible!important;float:none!important}'
  + '.bee{width:60px!important;height:60px!important;margin:0!important;padding:0!important;border:0!important;flex:none!important;order:0!important;position:static!important;align-self:auto!important;transform:none!important;float:none!important;box-sizing:border-box!important;font-size:40px;line-height:60px;text-align:center}';
function preview() {
  const l = LEVELS[GAME.level];
  const flowers = l.flowers.map(f => `<div class="flower" style="left:${f[0]}px;top:${f[1]}px">🌸</div>`).join('');
  const bees = '<div class="bee">🐝</div>'.repeat(l.bees);
  $('prev').srcdoc = `<style>${BASE}</style><style>${$('css').value.replace(/<\/style/gi, '')}</style><style>${FORCED}</style><div class="wrap">${flowers}<div id="garden">${bees}</div></div>`;
}
$('css').addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(preview, 200); });
function show(i) { const l = LEVELS[i]; t0 = Date.now(); $('ttl').textContent = (i + 1) + '. ' + l.title; $('task').textContent = l.task; $('css').value = l.css; $('msg').innerHTML = ''; preview(); GAME.tabs($('tabs'), LEVELS.length, show); }
$('chk').onclick = async () => {
  $('chk').disabled = true; $('msg').innerHTML = '';
  const j = await GAME.submit(GAME.level + 1, Math.round((Date.now() - t0) / 1000), {css: $('css').value}); $('chk').disabled = false;
  if (j.ok) { $('msg').innerHTML = `<div class="p-3 bg-green-100 text-green-800 rounded">🎉 Arılar çiçeklerde!${j.repeat === false ? ' +' + j.xp + ' XP' : ''}<div class="text-sm mt-1">${GAME.esc(j.explain || '')}</div></div>`; GAME.tabs($('tabs'), LEVELS.length, show); }
  else $('msg').innerHTML = `<div class="p-3 bg-red-100 text-red-800 rounded">${(j.messages || []).map(GAME.esc).join('<br>')}</div>`;
};
show(0);
</script>
@endsection
