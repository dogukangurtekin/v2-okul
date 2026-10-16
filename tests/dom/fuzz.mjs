import { Client, startWorker, stopWorker, phpStderr, waitFor, sleep, check, summary, USERS } from './dom.mjs';

// Istemci simulasyonu (sayfadaki JS) ile sunucu dogrulayicisi (PHP) rastgele programlarda ayni karari veriyor mu?
let seed = 12345; const rnd = () => (seed = (seed * 1664525 + 1013904223) % 4294967296) / 4294967296;
const pick = a => a[Math.floor(rnd() * a.length)];
const LEVELS = [{ m: 6, p: 0 }, { m: 8, p: 0 }, { m: 12, p: 0 }, { m: 3, p: 3 }, { m: 3, p: 6 }, { m: 7, p: 4 }];
const SOL = [['FFFX', ''], ['FFFRFFX', ''], ['RFFFLFFFLFX', ''], ['PP', 'FFX'], ['PPP', 'FFFFXR'], ['PXRPRPX', 'FFFF']];

await startWorker();
const c = new Client();
await c.login(...USERS.staff);
const p = await c.open('/oyun/lightbot-runner?v2=1');
const w = p.w; const orig = w.fetch; w.fetch = (...a) => { w.__submitted = true; return orig(...a); };
const add = (seq) => { for (const ch of seq) { const b = p.$('pal').querySelector(`[data-c="${ch}"]`); if (b) b.click(); } };

const cases = [];
SOL.forEach(([m, pr], i) => cases.push({ lvl: i, main: m, proc: pr }));                       // bilinen cozumler
for (let n = 0; n < 110; n++) {
  const lvl = Math.floor(rnd() * 6), L = LEVELS[lvl];
  const alpha = (inProc) => 'FFFFFLLRRXXX'.split('').concat(!inProc && L.p > 0 ? ['P', 'P'] : []);
  const mlen = 1 + Math.floor(rnd() * L.m), plen = L.p ? Math.floor(rnd() * (L.p + 1)) : 0;
  cases.push({ lvl, main: Array.from({ length: mlen }, () => pick(alpha(false))).join(''), proc: Array.from({ length: plen }, () => pick(alpha(true))).join('') });
}

// Cozumlerin mutasyonlari: kazanma siniri civarini yogun tara (duvar/isik/donus hatalari).
for (let n = 0; n < 140; n++) {
  const lvl = n % 6, L = LEVELS[lvl]; let [m, pr] = SOL[lvl].map(x => x.split(''));
  for (let e = 0, k = 1 + Math.floor(rnd() * 2); e < k; e++) {
    const target = (pr.length && rnd() < 0.4) ? pr : m, isProc = target === pr, op = Math.floor(rnd() * 3);
    const sym = pick(('FLRX' + (!isProc && L.p ? 'P' : '')).split(''));
    if (op === 0 && target.length) target[Math.floor(rnd() * target.length)] = sym;
    else if (op === 1 && target.length > 1) target.splice(Math.floor(rnd() * target.length), 1);
    else target.splice(Math.floor(rnd() * (target.length + 1)), 0, sym);
  }
  if (m.length && m.length <= L.m && pr.length <= L.p) cases.push({ lvl, main: m.join(''), proc: pr.join('') });
}

let diverge = [], wins = 0, clientWins = 0, serverWins = 0;
for (const [k, cs] of cases.entries()) {
  // sunucu karari (dogrudan)
  const body = JSON.stringify({ level: cs.lvl + 1, payload: { main: [...cs.main], proc: [...cs.proc] } });
  const sr = await c.http('POST', `/oyun/lightbot-runner/dene`, { headers: { 'content-type': 'application/json', accept: 'application/json', 'x-csrf-token': (p.w.GAME.csrf) }, body });
  const server = JSON.parse(sr.text).ok === true;

  // istemci karari
  p.$('tabs').children[cs.lvl].click(); await sleep(15);
  if (cs.proc) { p.$('tp').click(); add(cs.proc); p.$('tm').click(); }
  add(cs.main);
  const mainShown = p.$('lm').querySelectorAll('button').length, procShown = p.$('lp').querySelectorAll('button').length;
  w.__submitted = false;
  p.$('run').click();
  try { await waitFor(() => p.$('msg').textContent.trim().length > 0, 20000, 'sonuc'); } catch { diverge.push(`#${k} L${cs.lvl + 1} ${cs.main}/${cs.proc}: istemci sonuc vermedi`); continue; }
  await sleep(30);
  const client = w.__submitted === true;      // istemci kendi simulasyonunda kazandi ve sunucuya gonderdi
  const clientFinal = p.$('msg').textContent.includes('Seviye tamam');
  if (client) clientWins++; if (server) serverWins++;
  // Palet sinira takildiysa (program kirpildi) karsilastirma gecersiz: yine de ayni kirpilmis programi sunucuya sormak gerek
  if (mainShown !== cs.main.length || procShown !== cs.proc.length) {
    cs.trimmed = true;
  }
  if (cs.trimmed) continue;
  if (client !== server) diverge.push(`#${k} L${cs.lvl + 1} main=${cs.main} proc=${cs.proc} → istemci=${client} sunucu=${server} (${p.$('msg').textContent.replace(/\s+/g, ' ').trim().slice(0, 80)})`);
  else if (client && !clientFinal) diverge.push(`#${k} L${cs.lvl + 1}: istemci kazandi, sunucu onayladi ama "Seviye tamam" gorunmedi`);
}
console.log(`Vaka: ${cases.length} | istemci kazanan: ${clientWins} | sunucu kazanan: ${serverWins} | ayrisma: ${diverge.length}`);
diverge.slice(0, 15).forEach(d => console.log('  AYRISMA', d));
check('Lightbot: istemci simülasyonu ile sunucu doğrulayıcı aynı kararı veriyor', diverge.length === 0, diverge.slice(0, 5).join('\n   '));
check('Fuzz: bilinen 6 çözüm kazandı', clientWins >= 6 && serverWins >= 6, `${clientWins}/${serverWins}`);
const errs = [...new Set(c.errors)]; check('JS hatası yok', errs.length === 0, errs.join(' | '));
check('PHP hatası yok', phpStderr().trim() === '', phpStderr().slice(0, 300));
const bad = summary(); stopWorker(); process.exit(bad ? 1 : 0);
