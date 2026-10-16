@extends('layouts.app')
@section('title', $name)
@section('content')
<h1 class="text-2xl font-bold mb-1">{{ $cfg['emoji'] }} {{ $name }}</h1>
<p class="text-slate-500 mb-3">Python'u tarayıcında çalıştır. Görevi çöz, <b>Çalıştır</b>'la dene, çıktı doğruysa <b>Gönder</b>.
  <span class="text-xs">(Kodun yalnızca senin tarayıcında çalışır; ilk açılışta Python yüklenir, birkaç saniye sürebilir.)</span></p>
<div class="flex flex-wrap gap-2 mb-3 items-center"><button id="free" class="px-3 py-1 rounded border bg-white">✏️ Serbest</button><span id="tabs" class="flex flex-wrap gap-2"></span></div>
<div id="taskbox" class="bg-sky-50 border border-sky-200 rounded-xl p-3 mb-3 max-w-4xl"><b id="ttl"></b> — <span id="task"></span></div>
<div class="grid grid-cols-2 gap-4 max-w-5xl">
  <div class="space-y-2">
    <textarea id="code" rows="14" spellcheck="false" class="w-full border rounded-xl p-3 font-mono text-sm bg-slate-900 text-green-200"></textarea>
    <details><summary class="text-sm cursor-pointer text-slate-600">input() için girdi (her satıra bir değer)</summary><textarea id="stdin" rows="3" class="w-full border rounded p-2 font-mono text-sm bg-white mt-1"></textarea></details>
    <div class="flex gap-2"><button id="run" class="bg-green-600 text-white px-4 py-2 rounded">▶ Çalıştır <span class="text-xs opacity-70">(Ctrl+Enter)</span></button><button id="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Gönder</button></div>
    <div id="msg"></div>
  </div>
  <div><div class="text-sm font-semibold mb-1">Çıktı <span id="state" class="text-xs font-normal text-slate-400"></span></div>
    <pre id="out" class="bg-white border rounded-xl p-3 min-h-64 overflow-auto text-sm whitespace-pre-wrap"></pre></div>
</div>
@include('games.partials.common')
<script>
const LEVELS = @json($levels), PYODIDE_URL = @json($cfg['pyodide_url']);
const $ = id => document.getElementById(id);

/* ---- Python çalıştırıcı: Web Worker içinde Pyodide. Sonsuz döngüde worker öldürülüp yeniden kurulur. ---- */
function pyWorkerSource(indexURL) {
  return `
let py = null;
const dec = new TextDecoder();
self.onmessage = async (e) => {
  const { id, code, stdin } = e.data;
  const so = [], se = [];
  let error = '';
  try {
    if (!py) {
      importScripts(${JSON.stringify(indexURL + 'pyodide.js')});
      py = await loadPyodide({ indexURL: ${JSON.stringify(indexURL)} });
      self.postMessage({ ready: true });
    }
    const lines = String(stdin || '').split(/\\r?\\n/); if (lines.length && lines[lines.length - 1] === '') lines.pop();
    py.setStdin({ stdin: () => (lines.length ? lines.shift() : null) });
    py.setStdout({ raw: (b) => so.push(b & 255) });
    py.setStderr({ raw: (b) => se.push(b & 255) });
    const g = py.globals.get('dict')();
    try { await py.runPythonAsync(code, { globals: g }); } catch (ex) { error = String((ex && ex.message) || ex); }
    try { py.runPython('import sys\\nsys.stdout.flush()\\nsys.stderr.flush()'); } catch (_) {}
    g.destroy();
  } catch (ex) { error = 'Python yüklenemedi: ' + String((ex && ex.message) || ex); }
  self.postMessage({ id, ok: !error, stdout: dec.decode(new Uint8Array(so)), stderr: dec.decode(new Uint8Array(se)), error });
};`;
}
function createPyodideRunner(indexURL, timeoutMs = 8000) {
  let worker = null, onReady = null;
  const start = () => {
    const url = URL.createObjectURL(new Blob([pyWorkerSource(indexURL)], { type: 'text/javascript' }));
    worker = new Worker(url); URL.revokeObjectURL(url);
  };
  return {
    run(code, stdin) {
      return new Promise((resolve) => {
        if (!worker) start();
        const id = Date.now() + Math.random();
        const timer = setTimeout(() => { worker.terminate(); worker = null; resolve({ ok: false, stdout: '', stderr: '', error: 'Çok uzun sürdü (sonsuz döngü olabilir). Program durduruldu.', timeout: true }); }, timeoutMs + (worker.__loaded ? 0 : 60000));
        worker.onmessage = (ev) => {
          if (ev.data.ready) { worker.__loaded = true; if (onReady) onReady(); return; }
          if (ev.data.id !== id) return;
          clearTimeout(timer); worker.__loaded = true; resolve(ev.data);
        };
        worker.onerror = (ev) => { clearTimeout(timer); worker = null; resolve({ ok: false, stdout: '', stderr: '', error: 'Çalıştırıcı hatası: ' + (ev.message || 'bilinmiyor') }); };
        worker.postMessage({ id, code, stdin });
      });
    },
    onReady(fn) { onReady = fn; },
  };
}
const runner = window.PyRunner || createPyodideRunner(PYODIDE_URL);
if (runner.onReady) runner.onReady(() => { $('state').textContent = '· Python hazır'; });

/* ---- Arayüz ---- */
let free = false, t0 = Date.now(), lastRun = null;       // lastRun: { code, stdout } yalnızca hatasız son çalıştırma
function tail(err) { const l = String(err).trim().split('\n'); return l.slice(-4).join('\n'); }
function showLevel(i) {
  free = false; const l = LEVELS[i]; t0 = Date.now(); lastRun = null;
  $('taskbox').style.display = ''; $('submit').style.display = ''; $('ttl').textContent = (i + 1) + '. ' + l.title; $('task').textContent = l.task;
  $('code').value = l.starter; $('out').textContent = ''; $('msg').innerHTML = '';
  GAME.tabs($('tabs'), LEVELS.length, showLevel);
}
$('free').onclick = () => { free = true; lastRun = null; $('taskbox').style.display = 'none'; $('submit').style.display = 'none'; $('msg').innerHTML = ''; $('code').value = '# Serbest alan: istediğini dene\nprint("Merhaba!")\n'; $('out').textContent = ''; };
$('code').addEventListener('keydown', e => {
  if (e.key === 'Tab') { e.preventDefault(); const s = e.target.selectionStart; e.target.setRangeText('    ', s, e.target.selectionEnd, 'end'); }
  if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); $('run').click(); }
});
$('run').onclick = async () => {
  $('run').disabled = true; $('state').textContent = '· çalışıyor…'; $('msg').innerHTML = '';
  const code = $('code').value, r = await runner.run(code, $('stdin').value);
  $('run').disabled = false; $('state').textContent = '';
  const err = r.error || '', errText = err ? tail(err) : '';
  $('out').textContent = (r.stdout || '') + (r.stderr ? (r.stdout && !r.stdout.endsWith('\n') ? '\n' : '') + r.stderr : '') + (errText ? '\n' + errText : '');
  $('out').style.color = errText ? '#b91c1c' : '';
  lastRun = errText ? null : { code, stdout: r.stdout || '' };
};
$('submit').onclick = async () => {
  if (free) return;
  const code = $('code').value;
  if (!lastRun || lastRun.code !== code) { $('msg').innerHTML = '<div class="p-3 bg-amber-100 text-amber-800 rounded">Önce kodunu hatasız çalıştır; çıktı doğruysa gönder.</div>'; return; }
  $('submit').disabled = true;
  const j = await GAME.submit(GAME.level + 1, Math.round((Date.now() - t0) / 1000), { code, stdout: lastRun.stdout });
  $('submit').disabled = false;
  if (j.ok) { $('msg').innerHTML = `<div class="p-3 bg-green-100 text-green-800 rounded">🎉 Görev tamam!${j.repeat === false ? ' +' + j.xp + ' XP' : ''}<div class="text-sm mt-1">${GAME.esc(j.explain || '')}</div></div>`; GAME.tabs($('tabs'), LEVELS.length, showLevel); }
  else $('msg').innerHTML = `<div class="p-3 bg-red-100 text-red-800 rounded">${(j.messages || []).map(GAME.esc).join('<br>')}</div>`;
};
showLevel(0);
</script>
@endsection
