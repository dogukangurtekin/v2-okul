@extends('layouts.app')
@section('title', $name)
@section('content')
<h1 class="text-2xl font-bold mb-1">{{ $cfg['emoji'] }} {{ $name }}</h1>
<p class="text-slate-500 mb-3">Kodu çalıştırmadan, ekrana ne yazdırılacağını tahmin et.</p>
<div class="flex flex-wrap gap-2 mb-4" id="tabs"></div>
<div class="grid grid-cols-2 gap-6 max-w-4xl">
  <div><div id="ttl" class="font-semibold mb-1"></div><pre id="code" class="bg-slate-900 text-green-300 rounded-xl p-4 overflow-auto text-sm leading-6"></pre></div>
  <div><div class="font-semibold mb-1">Çıktı nedir? <span id="lines" class="text-xs text-slate-400"></span></div>
    <textarea id="ans" rows="6" class="w-full border rounded-xl p-3 font-mono bg-white" spellcheck="false" placeholder="Her satıra bir çıktı yaz"></textarea>
    <button id="chk" class="mt-2 bg-indigo-600 text-white px-4 py-2 rounded">Kontrol et</button>
    <div id="msg" class="mt-3"></div></div>
</div>
@include('games.partials.common')
<script>
const LEVELS = @json($levels); let t0 = Date.now();
const $ = id => document.getElementById(id);
function show(i) {
  const l = LEVELS[i]; t0 = Date.now();
  $('ttl').textContent = (i + 1) + '. ' + l.title;
  $('code').innerHTML = l.code.split('\n').map((ln, k) => `<span class="text-slate-500 select-none">${String(k + 1).padStart(2, ' ')}  </span>${GAME.esc(ln)}`).join('\n');
  $('lines').textContent = '(' + l.lines + ' satır)'; $('ans').value = ''; $('ans').rows = Math.max(3, l.lines + 1); $('msg').innerHTML = '';
  GAME.tabs($('tabs'), LEVELS.length, show);
}
$('chk').onclick = async () => {
  const i = GAME.level; $('chk').disabled = true;
  const j = await GAME.submit(i + 1, Math.round((Date.now() - t0) / 1000), {answer: $('ans').value});
  $('chk').disabled = false;
  if (j.ok) {
    $('msg').innerHTML = `<div class="p-3 bg-green-100 text-green-800 rounded">🎉 Doğru!${j.repeat === false ? ' +' + j.xp + ' XP' : ''}<div class="text-sm mt-1">${GAME.esc(j.explain || '')}</div></div>`;
    GAME.tabs($('tabs'), LEVELS.length, show);
  } else $('msg').innerHTML = `<div class="p-3 bg-red-100 text-red-800 rounded">${(j.messages || []).map(GAME.esc).join('<br>')}</div>`;
};
show(0);
</script>
@endsection
