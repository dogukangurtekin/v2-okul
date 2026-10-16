/*
 * jsdom + PHP köprüsü ile "tarayıcıya yakın" test donanımı (Windows / macOS / Linux).
 * Sayfaların satır içi betikleri gerçek Laravel yanıtlarıyla çalışır; fetch() doğrudan köprüye gider.
 * Kapsam dışı: CSS/Tailwind yerleşimi, tuval piksel çıktısı, iframe sandbox uygulaması, tarayıcıya özgü farklar.
 */
import { JSDOM, VirtualConsole } from 'jsdom';
import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export const HERE = path.dirname(fileURLToPath(import.meta.url));
export const ROOT = path.resolve(HERE, '..', '..');
export const CACHE = path.join(HERE, '.cache');
export const ORIGIN = 'http://app.test';
export const USERS = { student: ['ali@x.test', 'password1'], staff: ['admin@school.local', 'Admin1234!'] };
export const PHP = process.env.PHP_BIN || 'php';           // XAMPP: set PHP_BIN=C:\xampp\php\php.exe

/** Köprü ve seed için ortak ortam: .env'e dokunmadan, yalnızca test sqlite dosyasını kullanır. */
export function testEnv() {
  return {
    ...process.env,
    DOM_TESTING: '1', DB_CONNECTION: 'sqlite', DB_DATABASE: path.join(CACHE, 'test.sqlite'),
    APP_ENV: 'testing', APP_DEBUG: 'true', APP_URL: ORIGIN, SESSION_DRIVER: 'database', CACHE_STORE: 'array', QUEUE_CONNECTION: 'sync', MAIL_MAILER: 'array',
    APP_KEY: process.env.APP_KEY || 'base64:ZG9tLXRlc3Qta2V5LTAxMjM0NTY3ODlhYmNkZWZnaCE=',
    BCRYPT_ROUNDS: '4',
  };
}

let worker, buf = '', waiter = null, stderrBuf = '', chain = Promise.resolve();

export async function startWorker() {
  worker = spawn(PHP, [path.join(HERE, 'bridge.php')], { env: testEnv() });
  worker.on('error', e => { throw new Error(`PHP başlatılamadı (${PHP}): ${e.message}. PHP_BIN ortam değişkenini ayarlayın.`); });
  worker.stderr.on('data', d => { stderrBuf += d; });
  await new Promise((res, rej) => {
    worker.on('exit', c => rej(new Error('PHP işçisi kapandı: ' + (stderrBuf.slice(0, 600) || 'kod ' + c))));
    worker.stdout.on('data', d => {
      buf += d; let i;
      while ((i = buf.indexOf('\n')) >= 0) {
        const l = buf.slice(0, i).replace(/\r$/, ''); buf = buf.slice(i + 1);
        if (l === 'READY') res(); else if (waiter) { const w = waiter; waiter = null; w(l); }
      }
    });
  });
}
export const stopWorker = () => worker && worker.kill();
/** Deprecated bildirimleri (PHP sürüm farkı) hata sayılmaz. */
export const phpStderr = () => stderrBuf.split(/\r?\n/).filter(l => l.trim() && !/Deprecated/i.test(l)).join('\n');

function bridge(payload) {
  const run = () => new Promise((resolve, reject) => {
    waiter = l => { try { resolve(JSON.parse(l)); } catch { reject(new Error('köprü: ' + (stderrBuf || l).slice(0, 800))); } };
    worker.stdin.write(JSON.stringify(payload) + '\n');
  });
  const r = chain.then(run); chain = r.catch(() => {}); return r;
}

export class Client {
  constructor() { this.jar = new Map(); this.errors = []; this.requests = []; }

  async http(method, url, { headers = {}, body = null } = {}) {
    const full = url.startsWith('http') ? url : ORIGIN + url;
    const h = { ...headers, cookie: [...this.jar].map(([k, v]) => `${k}=${v}`).join('; ') };
    const r = await bridge({ url: full, method, headers: h, body });
    for (const c of r.cookies) { const [kv] = c.split(';'); const i = kv.indexOf('='); this.jar.set(kv.slice(0, i), kv.slice(i + 1)); }
    this.requests.push(`${method} ${full.replace(ORIGIN, '')} -> ${r.status}`);
    return { status: r.status, headers: r.headers, text: Buffer.from(r.body, 'base64').toString('utf8') };
  }

  async login(email, password) {
    const page = await this.http('GET', '/login');
    const token = page.text.match(/name="_token" value="([^"]+)"/)[1];
    const r = await this.http('POST', '/login', { headers: { 'content-type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ _token: token, email, password }).toString() });
    return r.status;
  }

  /** Sayfayı açar, satır içi betikleri çalıştırır. fetch() köprüye gider; tuval çağrıları sayılır (w.__draws). */
  async open(urlPath, opts = {}) {
    const res = await this.http('GET', urlPath);
    if (res.status !== 200) throw new Error(`${urlPath} -> ${res.status}: ${res.text.slice(0, 200)}`);
    const errors = this.errors, self = this, vc = new VirtualConsole();
    vc.on('jsdomError', e => errors.push('jsdomError: ' + (e.detail?.stack || e.stack || e.message).split('\n').slice(0, 3).join(' | ')));
    vc.on('error', (...a) => errors.push('console.error: ' + a.join(' ')));
    const dom = new JSDOM(res.text, {
      url: ORIGIN + urlPath, runScripts: 'dangerously', pretendToBeVisual: true, virtualConsole: vc,
      beforeParse(w) {
        const realST = w.setTimeout.bind(w);
        w.setTimeout = (fn, ms, ...a) => realST(fn, Math.max(1, (ms || 0) / 10), ...a);        // animasyonlar 10x hızlı
        w.__draws = [];
        w.HTMLCanvasElement.prototype.getContext = function () {
          const st = {};
          return new Proxy(st, { get: (t, p) => (p in t ? t[p] : (...a) => { w.__draws.push(String(p)); }), set: (t, p, v) => { t[p] = v; return true; } });
        };
        w.addEventListener('error', e => errors.push('window.error: ' + e.message));
        if (opts.beforeParse) opts.beforeParse(w);                                              // örn. w.PyRunner enjeksiyonu
        w.fetch = async (u, init = {}) => {
          const r = await self.http((init.method || 'GET').toUpperCase(), u, { headers: init.headers || {}, body: init.body || null });
          return { ok: r.status >= 200 && r.status < 300, status: r.status, json: async () => JSON.parse(r.text), text: async () => r.text };
        };
      },
    });
    const w = dom.window;
    return { dom, w, doc: w.document, $: id => w.document.getElementById(id), close: () => w.close() };
  }
}

export const sleep = ms => new Promise(r => setTimeout(r, ms));
export async function waitFor(fn, ms = 4000, what = 'koşul') {
  const t0 = Date.now();
  while (Date.now() - t0 < ms) { try { const v = fn(); if (v) return v; } catch {} await sleep(15); }
  throw new Error('zaman aşımı: ' + what);
}
export function input(el, value, w) { el.value = value; el.dispatchEvent(new w.Event('input', { bubbles: true })); }
export const answers = () => JSON.parse(fs.readFileSync(path.join(CACHE, 'answers.json'), 'utf8'));

export const results = [];
export function check(name, ok, detail = '') { results.push({ name, ok: !!ok, detail }); console.log((ok ? 'PASS ' : 'FAIL ') + name + (ok || !detail ? '' : '  → ' + detail)); }
export function summary() {
  const bad = results.filter(r => !r.ok);
  console.log(`\nSONUÇ: ${results.length - bad.length}/${results.length} geçti`);
  return bad.length;
}
process.on('unhandledRejection', e => { console.log('FAIL yakalanmamış promise reddi: ' + String(e?.stack || e).split('\n').slice(0, 3).join(' | ')); results.push({ name: 'unhandledRejection', ok: false }); });
