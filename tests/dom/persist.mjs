import { Client, startWorker, stopWorker, phpStderr, check, summary, answers, USERS } from './dom.mjs';
const ANS = answers().st;
await startWorker();
const c = new Client(); await c.login(...USERS.student);
const tabs = p => [...p.$('tabs').querySelectorAll('button')].map(b => b.disabled ? 'L' : (b.textContent.includes('✅') ? 'D' : 'o')).join('');
const post = (p, slug, level, payload) => c.http('POST', `/oyun/${slug}/sonuc`, { headers: { 'content-type': 'application/json', accept: 'application/json', 'x-csrf-token': p.w.GAME.csrf }, body: JSON.stringify({ level, seconds: 5, payload }) }).then(r => ({ status: r.status, ...(r.text.startsWith('{') ? JSON.parse(r.text) : {}) }));

let p = await c.open('/oyun/silent-teacher-runner');
for (let i = 0; i < 3; i++) await post(p, 'silent-teacher-runner', i + 1, { answer: ANS[i] });
p.close(); p = await c.open('/oyun/silent-teacher-runner');
check('Yeniden yükleme: ST sekmeleri ilerlemeyi hatırlıyor', tabs(p) === 'DDDo' + 'L'.repeat(8), tabs(p));
// Kilitli seviyeyi sunucuya doğrudan göndermek (sekme devre dışı olsa bile) reddedilmeli
const skip = await post(p, 'silent-teacher-runner', 9, { answer: ANS[8] });
check('Seviye atlama sunucuda reddedilir (422)', skip.status === 422, JSON.stringify(skip));
const again = await post(p, 'silent-teacher-runner', 1, { answer: ANS[0] });
check('Çözülmüş seviye tekrar çözülünce XP yok', again.ok === true && again.xp === 0 && again.repeat === true, JSON.stringify(again));
p.close();

p = await c.open('/oyun/lightbot-runner');
await post(p, 'lightbot-runner', 1, { main: ['F', 'F', 'F', 'X'], proc: [] });
p.close(); p = await c.open('/oyun/lightbot-runner');
check('Yeniden yükleme: Lightbot sekmeleri', tabs(p) === 'DoLLLL', tabs(p));
p.close();

p = await c.open('/oyun/web-lab-runner');
await post(p, 'web-lab-runner', 1, { html: '<h1>Merhaba</h1>', css: '' });
p.close(); p = await c.open('/oyun/web-lab-runner');
check('Yeniden yükleme: Web Atölyesi sekmeleri', tabs(p) === 'Do' + 'L'.repeat(6), tabs(p));
p.close();

const dash = await c.http('GET', '/dashboard');
const xp = dash.text.match(/data-count="(\d+)">\d+<\/span> XP/)?.[1];   // kahraman kartındaki toplam XP
check('Panel toplam XP = 3×10 + 12 + 12', xp === '54', 'XP=' + xp);
const errs = [...new Set(c.errors)]; check('JS hatası yok', errs.length === 0, errs.join(' | '));
check('PHP hatası yok', phpStderr().trim() === '', phpStderr().slice(0, 300));
const bad = summary(); stopWorker(); process.exit(bad ? 1 : 0);
