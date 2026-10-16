@extends('layouts.app')
@section('title', $name)
@section('content')
<h1 class="text-2xl font-bold mb-1">{{ $cfg['emoji'] }} {{ $name }}</h1>
<p class="text-slate-500 mb-3">Görevi oku, HTML ve CSS yaz, sağda sonucu gör. <span class="text-xs">(Bu atölyede JavaScript çalıştırılmaz.)</span></p>
<div class="flex flex-wrap gap-2 mb-4" id="tabs"></div>
<div class="bg-indigo-50 border border-indigo-200 rounded-xl p-3 mb-4 max-w-4xl"><b id="ttl"></b> — <span id="task"></span></div>
<div class="grid grid-cols-2 gap-4 max-w-5xl">
  <div class="space-y-2">
    <label class="text-sm font-semibold">HTML</label><textarea id="html" rows="9" spellcheck="false" class="w-full border rounded-xl p-3 font-mono text-sm bg-white"></textarea>
    <label class="text-sm font-semibold">CSS</label><textarea id="css" rows="7" spellcheck="false" class="w-full border rounded-xl p-3 font-mono text-sm bg-white"></textarea>
    <button id="chk" class="bg-indigo-600 text-white px-4 py-2 rounded">Kontrol et</button><div id="msg" class="mt-2"></div>
  </div>
  <div><label class="text-sm font-semibold">Önizleme</label>
    <iframe id="prev" sandbox="" class="w-full bg-white border rounded-xl" style="height:26rem" title="Önizleme"></iframe></div>
</div>
@include('games.partials.common')
<script>
const LEVELS = @json($levels); const $ = id => document.getElementById(id); let t0 = Date.now(), timer = null;
// sandbox="" => betik, form, bagimsiz kaynak yok: ogrenci kodu guvenle yalnizca gosterilir.
function preview() { $('prev').srcdoc = '<style>body{font-family:sans-serif;margin:12px}</style><style>' + $('css').value.replace(/<\/style/gi, '') + '</style>' + $('html').value; }
['html', 'css'].forEach(id => $(id).addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(preview, 250); }));
function show(i) { const l = LEVELS[i]; t0 = Date.now(); $('ttl').textContent = (i + 1) + '. ' + l.title; $('task').textContent = l.task; $('html').value = l.html; $('css').value = l.css || ''; $('msg').innerHTML = ''; preview(); GAME.tabs($('tabs'), LEVELS.length, show); }
$('chk').onclick = async () => {
  $('chk').disabled = true; const j = await GAME.submit(GAME.level + 1, Math.round((Date.now() - t0) / 1000), {html: $('html').value, css: $('css').value}); $('chk').disabled = false;
  if (j.ok) { $('msg').innerHTML = `<div class="p-3 bg-green-100 text-green-800 rounded">🎉 Görev tamam!${j.repeat === false ? ' +' + j.xp + ' XP' : ''}<div class="text-sm mt-1">${GAME.esc(j.explain || '')}</div></div>`; GAME.tabs($('tabs'), LEVELS.length, show); }
  else $('msg').innerHTML = `<div class="p-3 bg-red-100 text-red-800 rounded"><ul class="list-disc pl-5">${(j.messages || []).map(m => '<li>' + GAME.esc(m) + '</li>').join('')}</ul></div>`;
};
show(0);
</script>
@endsection
