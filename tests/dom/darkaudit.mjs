/*
 * Gece modu denetimi (tarayıcı gerektirmez): public/css/app.css'i okuyup jsdom DOM'una kendi basit CSS kaskadıyla uygular
 * (katmanlar, özgüllük, !important, var(), oklch dahil) ve metin/arka plan kontrastını ölçer.
 *   node tests/dom/darkaudit.mjs [--theme=dark|light] [--staff|--student] [--max=60]
 * Çıktı: kontrastı < 3 olan öğeler ve gece modunda açık renkli kalan yüzeyler.
 */
import fs from 'node:fs';
import path from 'node:path';
import { Client, startWorker, stopWorker, USERS, ROOT } from './dom.mjs';

const arg = k => (process.argv.find(a => a.startsWith(`--${k}=`)) || '').split('=')[1];
const THEME = arg('theme') || 'dark', MAX = +(arg('max') || 60), AS = process.argv.includes('--student') ? 'student' : 'staff';
const VIEW_W = 1280;

/* ---------------- CSS ayrıştırıcı ---------------- */
const css = fs.readFileSync(path.join(ROOT, 'public/css/app.css'), 'utf8').replace(/\/\*[\s\S]*?\*\//g, '');
const layerOrder = []; const rules = []; let order = 0;
function matchBrace(s, i) { let d = 0, q = null; for (; i < s.length; i++) { const c = s[i]; if (q) { if (c === q && s[i - 1] !== '\\') q = null; continue; } if (c === '"' || c === "'") { q = c; continue; } if (c === '{') d++; else if (c === '}') { d--; if (!d) return i; } } return s.length - 1; }
function mediaOk(q) {
  q = q.trim().toLowerCase(); if (!q || q === 'all' || q === 'screen') return true;
  if (/print|prefers-reduced-motion|prefers-color-scheme|forced-colors|pointer:\s*coarse|orientation/.test(q)) return false;
  return q.split(/\band\b|,/).every(p => {
    let m = p.match(/min-width:\s*([\d.]+)(px|rem|em)/); if (m) return VIEW_W >= (+m[1]) * (m[2] === 'px' ? 1 : 16);
    m = p.match(/max-width:\s*([\d.]+)(px|rem|em)/); if (m) return VIEW_W <= (+m[1]) * (m[2] === 'px' ? 1 : 16);
    m = p.match(/width\s*>=\s*([\d.]+)(px|rem)/); if (m) return VIEW_W >= (+m[1]) * (m[2] === 'px' ? 1 : 16);
    return true;
  });
}
function splitTop(s, sep) { const out = []; let d = 0, q = null, cur = ''; for (const c of s) { if (q) { cur += c; if (c === q) q = null; continue; } if (c === '"' || c === "'") { q = c; cur += c; continue; } if (c === '(' || c === '[') d++; if (c === ')' || c === ']') d--; if (c === sep && !d) { out.push(cur); cur = ''; } else cur += c; } if (cur.trim()) out.push(cur); return out; }
function parseDecls(body) { const out = []; for (const d of splitTop(body, ';')) { const i = d.indexOf(':'); if (i < 0) continue; let prop = d.slice(0, i).trim().toLowerCase(), val = d.slice(i + 1).trim(); const imp = /!important\s*$/i.test(val); val = val.replace(/\s*!important\s*$/i, ''); out.push({ prop, val, imp }); } return out; }
function parse(s, layer, mediaPass) {
  let i = 0;
  while (i < s.length) {
    const b = s.indexOf('{', i), sc = s.indexOf(';', i);
    if (sc >= 0 && (b < 0 || sc < b)) { const st = s.slice(i, sc).trim(); const m = st.match(/^@layer\s+(.+)$/); if (m) m[1].split(',').forEach(n => { n = n.trim(); if (!layerOrder.includes(n)) layerOrder.push(n); }); i = sc + 1; continue; }
    if (b < 0) break;
    const pre = s.slice(i, b).trim(), e = matchBrace(s, b), inner = s.slice(b + 1, e); i = e + 1;
    if (pre.startsWith('@')) {
      let m;
      if ((m = pre.match(/^@layer\s*(.*)$/))) { const n = m[1].trim() || '(anon)'; if (!layerOrder.includes(n)) layerOrder.push(n); parse(inner, n, mediaPass); }
      else if ((m = pre.match(/^@media\s+(.*)$/))) { if (mediaOk(m[1])) parse(inner, layer, mediaPass); }
      continue;
    }
    const decls = parseDecls(inner);
    for (const sel of splitTop(pre, ',')) rules.push({ sel: sel.trim(), decls, layer, order: order++ });
  }
}
parse(css, null);
const layerRank = n => (n == null ? 1000 : layerOrder.indexOf(n));
function spec(sel) {
  const inner = (re, rep) => { let s = sel; for (;;) { const m = s.match(re); if (!m) return s; s = s.replace(m[0], rep(m)); } };
  let a = 0, b = 0, c = 0, s = sel.replace(/:where\([^()]*(?:\([^()]*\)[^()]*)*\)/g, '');
  s = s.replace(/:(is|not|has)\(((?:[^()]|\([^()]*\))*)\)/g, (m, f, args) => { const best = splitTop(args, ',').map(spec).sort((x, y) => (y[0] - x[0]) || (y[1] - x[1]) || (y[2] - x[2]))[0] || [0, 0, 0]; a += best[0]; b += best[1]; c += best[2]; return ''; });
  a += (s.match(/#[\w-]+/g) || []).length; s = s.replace(/#[\w-]+/g, '');
  b += (s.match(/\[[^\]]*\]/g) || []).length; s = s.replace(/\[[^\]]*\]/g, '');
  c += (s.match(/::[\w-]+/g) || []).length; s = s.replace(/::[\w-]+/g, '');
  b += (s.match(/:[\w-]+(\([^)]*\))?/g) || []).length; s = s.replace(/:[\w-]+(\([^)]*\))?/g, '');
  b += (s.match(/\.[\w\\:-]+/g) || []).length; s = s.replace(/\.[\w\\:-]+/g, '');
  c += (s.match(/[a-z][\w-]*/gi) || []).length;
  return [a, b, c];
}
const SKIP_SEL = /::|:hover|:focus|:active|:visited|:checked|:disabled|:placeholder|:focus-within|:focus-visible|:target|:-webkit|:-moz|:autofill|:indeterminate|:open|:not\(:focus/;
function lastCompound(sel) { let d = 0; for (let i = sel.length - 1; i >= 0; i--) { const ch = sel[i]; if (ch === ')' || ch === ']') d++; else if (ch === '(' || ch === '[') d--; else if (!d && /[\s>+~]/.test(ch)) return sel.slice(i + 1); } return sel; }
const idx = { cls: new Map(), tag: new Map(), any: [] };
for (const r of rules) {
  if (SKIP_SEL.test(r.sel) || r.sel.startsWith('@')) continue;
  r.spec = spec(r.sel);
  const last = lastCompound(r.sel);
  const cm = /[()]/.test(last) ? [] : [...last.matchAll(/\.((?:\\.|[\w-])+)/g)].map(m => m[1].replace(/\\(.)/g, '$1'));
  const key = cm[cm.length - 1];
  if (key) (idx.cls.get(key) || idx.cls.set(key, []).get(key)).push(r);
  else { const tm = /[()]/.test(last) ? null : last.match(/^[a-z][\w-]*/i); if (tm) (idx.tag.get(tm[0].toLowerCase()) || idx.tag.set(tm[0].toLowerCase(), []).get(tm[0].toLowerCase())).push(r); else idx.any.push(r); }
}

/* ---------------- Renk ---------------- */
const NAMED = { white: [255, 255, 255, 1], black: [0, 0, 0, 1], transparent: [0, 0, 0, 0], red: [255, 0, 0, 1] };
function oklch2rgb(L, C, h, a = 1) {
  const hr = h * Math.PI / 180, A = C * Math.cos(hr), B = C * Math.sin(hr);
  const l_ = L + 0.3963377774 * A + 0.2158037573 * B, m_ = L - 0.1055613458 * A - 0.0638541728 * B, s_ = L - 0.0894841775 * A - 1.2914855480 * B;
  const l = l_ ** 3, m = m_ ** 3, s = s_ ** 3;
  const lin = [4.0767416621 * l - 3.3077115913 * m + 0.2309699292 * s, -1.2684380046 * l + 2.6097574011 * m - 0.3413193965 * s, -0.0041960863 * l - 0.7034186147 * m + 1.7076147010 * s];
  return [...lin.map(x => { x = Math.min(1, Math.max(0, x)); return Math.round(255 * (x <= 0.0031308 ? 12.92 * x : 1.055 * x ** (1 / 2.4) - 0.055)); }), a];
}
function parseColor(v) {
  if (v == null) return null; v = v.trim().toLowerCase();
  if (NAMED[v]) return NAMED[v];
  let m;
  if ((m = v.match(/^#([0-9a-f]{3,8})$/))) { let h = m[1]; if (h.length <= 4) h = [...h].map(c => c + c).join(''); return [parseInt(h.slice(0, 2), 16), parseInt(h.slice(2, 4), 16), parseInt(h.slice(4, 6), 16), h.length === 8 ? parseInt(h.slice(6, 8), 16) / 255 : 1]; }
  if ((m = v.match(/^rgba?\((.+)\)$/))) { const p = m[1].split(/[\s,\/]+/).filter(Boolean); const n = x => (x.endsWith('%') ? parseFloat(x) * 2.55 : parseFloat(x)); return [n(p[0]), n(p[1]), n(p[2]), p[3] != null ? (p[3].endsWith('%') ? parseFloat(p[3]) / 100 : parseFloat(p[3])) : 1]; }
  if ((m = v.match(/^oklch\((.+)\)$/))) { const p = m[1].split(/[\s\/]+/).filter(Boolean); const L = p[0].endsWith('%') ? parseFloat(p[0]) / 100 : parseFloat(p[0]); return oklch2rgb(L, parseFloat(p[1]), parseFloat(p[2]) || 0, p[3] != null ? parseFloat(p[3]) : 1); }
  if ((m = v.match(/^hsla?\((.+)\)$/))) { const p = m[1].split(/[\s,\/]+/).filter(Boolean); const h = parseFloat(p[0]) / 360, s = parseFloat(p[1]) / 100, l = parseFloat(p[2]) / 100; const f = n => { const k = (n + h * 12) % 12, a2 = s * Math.min(l, 1 - l); return Math.round(255 * (l - a2 * Math.max(-1, Math.min(k - 3, 9 - k, 1)))); }; return [f(0), f(8), f(4), p[3] != null ? parseFloat(p[3]) : 1]; }
  return null;
}
const COLOR_TOKEN = /#[0-9a-f]{3,8}\b|(?:rgba?|hsla?|oklch)\([^)]*\)|\b(?:white|black|transparent)\b/gi;
function blend(top, bot) { const a = top[3] + bot[3] * (1 - top[3]); if (a === 0) return [0, 0, 0, 0]; return [0, 1, 2].map(i => (top[i] * top[3] + bot[i] * bot[3] * (1 - top[3])) / a).concat(a); }
const lum = c => { const f = x => { x /= 255; return x <= 0.03928 ? x / 12.92 : ((x + 0.055) / 1.055) ** 2.4; }; return 0.2126 * f(c[0]) + 0.7152 * f(c[1]) + 0.0722 * f(c[2]); };
const ratio = (a, b) => { const l1 = lum(a), l2 = lum(b); return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05); };

/* ---------------- Kaskad ---------------- */
function resolveVars(val, vars, depth = 0) {
  if (depth > 8 || !val.includes('var(')) return val;
  return resolveVars(val.replace(/var\(\s*(--[\w-]+)\s*(?:,\s*((?:[^()]|\([^()]*\))*))?\)/g, (m, name, fb) => (vars[name] != null ? vars[name] : fb != null ? fb.trim() : 'INVALID')), vars, depth + 1);
}
function styleDecls(el) { const s = el.getAttribute('style'); return s ? parseDecls(s) : []; }
const cache = new WeakMap();
function compute(el) {
  if (cache.has(el)) return cache.get(el);
  const parent = el.parentElement, pc = parent ? compute(parent) : { vars: {}, color: [0, 0, 0, 1] };
  const cands = new Set(); const add = l => l && l.forEach(r => cands.add(r));
  for (const c of el.classList) add(idx.cls.get(c)); add(idx.tag.get(el.localName)); add(idx.any);
  const matched = [];
  for (const r of cands) { let ok = false; try { ok = el.matches(r.sel); } catch { ok = false; } if (ok) matched.push(r); }
  const items = [];
  for (const r of matched) for (const d of r.decls) items.push({ ...d, rank: layerRank(r.layer), spec: r.spec, order: r.order });
  for (const d of styleDecls(el)) items.push({ ...d, rank: 2000, spec: [9, 9, 9], order: 1e9 });
  const key = d => [d.imp ? 1 : 0, d.imp ? (d.rank >= 1000 ? 0 : 1000 - d.rank) : d.rank, ...d.spec, d.order];
  items.sort((x, y) => { const a = key(x), b = key(y); for (let i = 0; i < a.length; i++) if (a[i] !== b[i]) return a[i] - b[i]; return 0; });
  const vars = { ...pc.vars }; let colorV = null, bgV = null, display = null;
  for (const d of items) if (d.prop.startsWith('--')) vars[d.prop] = d.val;
  for (const d of items) {
    if (d.prop.startsWith('--')) continue;
    const val = resolveVars(d.val, vars);
    if (val.includes('INVALID')) continue;
    if (d.prop === 'color') colorV = val;
    else if (d.prop === 'background-color' || d.prop === 'background') bgV = val;
    else if (d.prop === 'display') display = val;
  }
  let color = pc.color;
  if (colorV != null) { if (colorV === 'inherit' || colorV === 'currentcolor') color = pc.color; else { const c = parseColor(colorV); if (c) color = c; } }
  let bg = null, gradient = false;
  if (bgV != null) {
    if (/gradient\(/.test(bgV)) { const cs = (bgV.match(COLOR_TOKEN) || []).map(parseColor).filter(Boolean); if (cs.length) { bg = [0, 1, 2].map(i => cs.reduce((s, c) => s + c[i], 0) / cs.length).concat(Math.min(...cs.map(c => c[3]))); gradient = true; } }
    else { const toks = bgV.match(COLOR_TOKEN); if (toks) bg = parseColor(toks[0]); else if (/^(none|transparent)$/i.test(bgV.trim())) bg = [0, 0, 0, 0]; }
  }
  const out = { vars, color, bg, display, gradient };
  cache.set(el, out); return out;
}
function effectiveBg(el) {
  const stack = []; for (let e = el; e; e = e.parentElement) { const b = compute(e).bg; if (b) { stack.push(b); if (b[3] >= 0.99) break; } }
  let acc = [255, 255, 255, 1]; for (let i = stack.length - 1; i >= 0; i--) acc = blend(stack[i], acc); return acc;
}
const sig = el => { const c = [...el.classList].slice(0, 3).join('.'); return el.localName + (c ? '.' + c : ''); };
const path3 = el => { const p = []; for (let e = el; e && p.length < 3; e = e.parentElement) p.unshift(sig(e)); return p.join(' > '); };

/* ---------------- Sayfalar ---------------- */
await startWorker();
const c = new Client(); await c.login(...(AS === 'staff' ? USERS.staff : USERS.student));
if (arg('band')) { const b = await c.open('/dashboard?band=' + arg('band')); b.close(); }
const SKIP_URL = /logout|\/delete|export|indir|csv|pdf|print|yazdir|runner-|\/oyun\/|download|login|qr\/|card\/|veli|storage|\.(png|jpg|svg|js|css)$|\/up$|webpush|manifest|service-worker/i;
const seen = new Set(['/dashboard']), queue = ['/dashboard']; const pages = [];
const flagged = new Map(), surfaces = new Map();
function audit(doc, url) {
  doc.documentElement.dataset.theme = THEME;
  const body = doc.body; const walker = doc.createTreeWalker(body, 1);
  for (let el = walker.currentNode; el; el = walker.nextNode()) {
    const tag = el.localName; if (['script', 'style', 'svg', 'path', 'template', 'noscript', 'option'].includes(tag)) continue;
    if (el.closest('[hidden], template, svg')) continue;
    const st = compute(el); if (st.display === 'none') continue;
    let hidden = false; for (let e = el.parentElement; e; e = e.parentElement) if (compute(e).display === 'none') { hidden = true; break; } if (hidden) continue;
    if (el.classList.contains('hidden') || el.closest('.hidden')) continue;
    const own = [...el.childNodes].filter(n => n.nodeType === 3 && n.textContent.trim()).map(n => n.textContent.trim()).join(' ');
    const isField = ['input', 'select', 'textarea'].includes(tag) && !['hidden', 'checkbox', 'radio', 'submit', 'file', 'range'].includes(el.type);
    const hasText = /[\p{L}\p{N}]/u.test(own.replace(/\p{Extended_Pictographic}/gu, ''));
    if ((!own || !hasText) && !isField) {
      // metinsiz ama açık renkli büyük yüzey (kart/kutu) – gece modunda açık kalmış mı?
      if (THEME === 'dark' && st.bg && st.bg[3] >= 0.99 && lum(st.bg) > 0.55 && el.children.length) { const k = sig(el) + ' bg=' + st.bg.slice(0, 3).map(Math.round).join(','); if (!surfaces.has(k)) surfaces.set(k, { n: 0, url, path: path3(el) }); surfaces.get(k).n++; }
      continue;
    }
    let bg = effectiveBg(el); const fg = blend([st.color[0], st.color[1], st.color[2], st.color[3]], bg);
    const r = ratio(fg, bg);
    const k = sig(el) + '|' + fg.slice(0, 3).map(Math.round) + '|' + bg.slice(0, 3).map(Math.round);
    if (THEME === 'dark' && st.bg && st.bg[3] >= 0.99 && lum(st.bg) > 0.55) { const sk = sig(el) + ' bg=' + st.bg.slice(0, 3).map(Math.round).join(','); if (!surfaces.has(sk)) surfaces.set(sk, { n: 0, url, path: path3(el) }); surfaces.get(sk).n++; }
    if (r < 3) { const f = flagged.get(k) || { n: 0, url, path: path3(el), text: (own || el.value || '').slice(0, 40), r: r.toFixed(2), fg: fg.slice(0, 3).map(Math.round), bg: bg.slice(0, 3).map(Math.round) }; f.n++; flagged.set(k, f); }
  }
}
while (queue.length && pages.length < MAX) {
  const u = queue.shift(); let p;
  try { p = await c.open(u); } catch (e) { console.log('! açılamadı', u, String(e.message).slice(0, 80)); continue; }
  pages.push(u);
  for (const a of p.doc.querySelectorAll('a[href]')) { let h = a.getAttribute('href'); if (!h || h.startsWith('#') || /^(mailto|tel|javascript)/.test(h)) continue; try { const U = new URL(h, 'http://app.test'); if (U.host !== 'app.test') continue; h = U.pathname + U.search; } catch { continue; } if (SKIP_URL.test(h) || seen.has(h)) continue; seen.add(h); queue.push(h); }
  audit(p.doc, u); p.close();
}
stopWorker();
console.log(`\n${pages.length} sayfa tarandı (${AS}, tema=${THEME}); kural sayısı=${rules.length}\n`);
console.log('── Düşük kontrast (<3:1) ──');
for (const f of [...flagged.values()].sort((a, b) => a.r - b.r).slice(0, 60)) console.log(`${f.r}  ×${f.n}  "${f.text}"  fg=${f.fg} bg=${f.bg}\n      ${f.path}   (${f.url})`);
console.log('\n── Gece modunda açık kalan yüzeyler ──');
for (const [k, v] of [...surfaces].sort((a, b) => b[1].n - a[1].n).slice(0, 40)) console.log(`×${v.n}  ${k}\n      ${v.path}   (${v.url})`);
process.exit(0);
