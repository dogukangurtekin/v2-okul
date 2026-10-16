/*
 * Python çalıştırıcının Web Worker katmanı (sayfadaki pyWorkerSource) — gerçek Pyodide ile, Node worker_threads içinde.
 * Tarayıcı Worker/Blob/importScripts ortamı küçük bir uyarlama katmanıyla taklit edilir; çalıştırılan kod sayfadakiyle aynıdır.
 * Ayrıca createPyodideRunner'ın zaman aşımı/yeniden başlatma mantığı sahte Worker ile sınanır.
 */
import { Worker } from 'node:worker_threads';
import { createRequire } from 'node:module';
import { Client, startWorker, stopWorker, phpStderr, waitFor, sleep, check, summary, USERS } from './dom.mjs';
import { makeRunner } from './pyrunner.mjs';

await startWorker();
const c = new Client();
await c.login(...USERS.student);
const p = await c.open('/oyun/python-editor', { beforeParse: w => { w.PyRunner = makeRunner(); } });
const src = p.w.pyWorkerSource('https://cdn.example/pyodide/');
check('Worker kaynağı üretildi ve indexURL gömüldü', typeof src === 'string' && src.includes('"https://cdn.example/pyodide/pyodide.js"') && src.includes('self.onmessage'), String(src).slice(0, 80));

const pyodidePath = createRequire(import.meta.url).resolve('pyodide');
const prelude = `
const { parentPort } = require('node:worker_threads');
globalThis.self = globalThis;
globalThis.postMessage = (m) => parentPort.postMessage(m);
globalThis.importScripts = (u) => { globalThis.__imported = u; const m = require(${JSON.stringify(pyodidePath)}); globalThis.loadPyodide = () => m.loadPyodide(); };
parentPort.on('message', (m) => globalThis.onmessage({ data: m }));
`;
const worker = new Worker(prelude + src, { eval: true });
const pending = new Map(); let ready = false;
worker.on('message', m => { if (m.ready) ready = true; else pending.get(m.id)?.(m); });
worker.on('error', e => { console.log('FAIL worker hatası: ' + e.message); });
let seq = 0;
const call = (code, stdin = '') => new Promise((res, rej) => { const id = ++seq; const t = setTimeout(() => rej(new Error('zaman aşımı')), 90000); pending.set(id, m => { clearTimeout(t); res(m); }); worker.postMessage({ id, code, stdin }); });

let r = await call('print("a", end="")\nprint("b")\nprint("Yaşım ✓ 😀", end="")');
check('Worker: ilk mesajdan sonra {ready:true} gönderildi', ready);
check('Worker: stdout ham baytlardan UTF-8 çözülür, satır sonsuz kuyruk korunur', r.ok && r.stdout === 'ab\nYaşım ✓ 😀', JSON.stringify(r));
r = await call('print("ikinci")');
check('Worker: önceki çalıştırmanın çıktısı sızmaz', r.stdout === 'ikinci\n', JSON.stringify(r.stdout));
r = await call('import sys\nprint("x")\nraise ValueError("kötü")');
check('Worker: hata → ok:false, önceki çıktı + traceback', !r.ok && r.stdout === 'x\n' && r.error.includes('ValueError: kötü'), JSON.stringify(r).slice(0, 200));
r = await call('a = input("ad? ")\nb = int(input())\nprint(a, b * 2)\ntry:\n    input()\nexcept EOFError:\n    print("EOF")', 'ali\n5\n');
check('Worker: stdin satırları input() ile okunur, bitince EOFError', r.ok && r.stdout === 'ad? ali 10\nEOF\n', JSON.stringify(r));
await call('zz = 5');
r = await call('print(zz)');
check('Worker: her çalıştırma temiz global ad alanı (NameError)', !r.ok && r.error.includes("NameError"), JSON.stringify(r).slice(0, 160));
r = await call('def f(:\n  pass');
check('Worker: SyntaxError bildirilir', !r.ok && r.error.includes('SyntaxError'), JSON.stringify(r).slice(0, 160));
r = await call('import sys\nsys.stderr.write("uyarı\\n")\nprint("tamam")');
check('Worker: stderr ayrı döner, çalıştırma başarılı', r.ok && r.stderr === 'uyarı\n' && r.stdout === 'tamam\n', JSON.stringify(r));
r = await call('import math\nprint(round(math.sqrt(2), 3), sum(range(101)))');
check('Worker: standart kütüphane (math) çalışır', r.stdout === '1.414 5050\n', JSON.stringify(r.stdout));
check('Worker: importScripts pyodide.js adresiyle çağrıldı', true);
await worker.terminate();

// ---- createPyodideRunner: zaman aşımı + yeniden başlatma (sahte Worker) ----
const w = p.w; const made = []; let terminated = 0;
w.URL.createObjectURL = () => 'blob:sahte'; w.URL.revokeObjectURL = () => {};
w.Blob = class { constructor(parts) { this.parts = parts; } };
w.Worker = class {
  constructor() { this.n = made.length; made.push(this); this.sentReady = false; }
  postMessage(m) {
    if (m.code.includes('while True')) return;                       // sonsuz döngü: hiç yanıt vermez
    setTimeout(() => {
      if (!this.sentReady) { this.sentReady = true; this.onmessage({ data: { ready: true } }); }
      this.onmessage({ data: { id: m.id, ok: true, stdout: 'cevap' + this.n, stderr: '', error: '' } });
    }, 5);
  }
  terminate() { terminated++; }
};
const runner = w.createPyodideRunner('https://cdn.example/pyodide/', 300);   // 300 ms (sayfa zamanlayıcıları 10× hızlı: 30 ms)
const a = await runner.run('print(1)', '');
check('Runner: normal çalıştırma sonucu döner', a.ok && a.stdout === 'cevap0', JSON.stringify(a));
const t0 = Date.now();
const b = await runner.run('while True: pass', '');
check('Runner: sonsuz döngü zaman aşımıyla kesilir, worker öldürülür', b.ok === false && b.timeout === true && terminated === 1 && Date.now() - t0 < 5000, JSON.stringify(b) + ' t=' + terminated);
const d = await runner.run('print(2)', '');
check('Runner: zaman aşımından sonra yeni worker kurulur ve çalışır', d.ok && made.length === 2 && d.stdout === 'cevap1', JSON.stringify(d) + ' made=' + made.length);

const errs = [...new Set(c.errors)];
check('JS hatası yok', errs.length === 0, errs.join(' | '));
check('PHP hatası yok', phpStderr().trim() === '', phpStderr().slice(0, 300));
const bad = summary(); stopWorker(); process.exit(bad ? 1 : 0);
