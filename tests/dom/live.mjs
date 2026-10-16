/* Canlı quiz (öğrenci oynatma sayfası): yeni arayüzün betiği gerçek sunucu akışıyla. Yönetici + öğrenci iki ayrı oturum. */
import { Client, startWorker, stopWorker, phpStderr, waitFor, sleep, check, summary, USERS } from './dom.mjs';

await startWorker();
const admin = new Client(), stu = new Client();
await admin.login(...USERS.staff); await stu.login(...USERS.student);

const token = async (c, path) => (await c.http('GET', path)).text.match(/name="_token" value="([^"]+)"/)[1];
const form = async (c, path, data, tokenFrom) => {
  const body = new URLSearchParams({ _token: await token(c, tokenFrom || path), ...data }).toString();
  return c.http('POST', path, { headers: { 'content-type': 'application/x-www-form-urlencoded' }, body });
};

// Quiz + oturum (yönetici)
let r = await form(admin, '/quizzes', {
  title: 'Arayüz testi',
  'questions[0][text]': '2 + 2 kaç eder?', 'questions[0][options]': '3\n4\n5', 'questions[0][correct]': '1', 'questions[0][duration]': '30', 'questions[0][xp]': '10',
  'questions[1][text]': 'Başkent?', 'questions[1][options]': 'İzmir\nAnkara', 'questions[1][correct]': '1', 'questions[1][duration]': '30', 'questions[1][xp]': '10', 'questions[1][double]': '1',
}, '/quizzes/create');
check('Quiz oluşturuldu', r.status === 302, 'status ' + r.status);
const quizId = (await admin.http('GET', '/quizzes')).text.match(/quizzes\/(\d+)\/edit/)[1];
r = await form(admin, '/canli', { type: 'quiz', quiz_id: quizId }, '/canli');
const sessionId = r.headers.Location.match(/canli\/(\d+)/)[1];
const code = (await admin.http('GET', `/canli/${sessionId}`)).text.match(/font-mono[^>]*>([A-Z0-9]{6})</)?.[1] || (await admin.http('GET', `/canli/${sessionId}`)).text.match(/>([A-Z2-9]{6})</)?.[1];
check('Oturum oluşturuldu, katılım kodu görünüyor', !!sessionId && /^[A-Z0-9]{6}$/.test(code || ''), 'kod=' + code);

// Öğrenci: katılma sayfası + katıl
const join = await stu.open('/ogrenci/canli');
check('Katılma sayfası: kod kutusu ve katıl düğmesi', !!join.doc.querySelector('input[name=code]') && join.doc.body.textContent.includes('Katıl'));
join.close();
r = await form(stu, '/ogrenci/canli', { code }, '/ogrenci/canli');
check('Koda katılım → oyun sayfasına yönlendi', r.status === 302 && r.headers.Location.includes(`/ogrenci/canli/${sessionId}`), r.status + ' ' + r.headers.Location);

// Oyun sayfası (bekleme)
const p = await stu.open(`/ogrenci/canli/${sessionId}`);
const stage = () => p.$('stage').textContent.replace(/\s+/g, ' ');
await waitFor(() => stage().includes('başlatması bekleniyor'), 6000, 'bekleme ekranı');
check('Bekleme ekranı + katılımcı sayısı', stage().includes('1 katılımcı'), stage());
check('Öğrenci kabuğu: seviye çubuğu ve alt menü var', !!p.doc.querySelector('.st-lvl .xpbar') && !!p.doc.querySelector('.st-bottom'));
check('Sıralama kutusu ilk katılımcıyı gösterir', p.$('board').textContent.includes('Ali'), p.$('board').textContent);

// Başlat → soru 1
await form(admin, `/canli/${sessionId}/baslat`, {}, `/canli/${sessionId}`);
await waitFor(() => p.doc.querySelectorAll('.q-opt').length === 3, 8000, 'soru seçenekleri');
check('Soru metni ve 3 renkli seçenek (Kahoot tarzı)', stage().includes('2 + 2 kaç eder?') && p.doc.querySelectorAll('.q-opt.c0, .q-opt.c1, .q-opt.c2').length === 3, stage().slice(0, 80));
check('Seçenekler şekil simgeli (▲ ◆ ●)', [...p.doc.querySelectorAll('.q-opt i')].map(e => e.textContent).join('') === '▲◆●');
await sleep(600);        // zamanlayıcı 250 ms'de bir güncellenir
check('Zamanlayıcı görünür ve geri sayıyor (≈30 sn)', p.$('timer').style.visibility === 'visible' && parseInt(p.$('timer').textContent) > 20, p.$('timer').textContent);
check('Başta doğru cevap işaretlenmiyor (sızıntı yok)', !p.doc.querySelector('.q-opt.right'));

// Cevapla
p.doc.querySelectorAll('.q-opt')[1].click();
await waitFor(() => p.doc.querySelector('.q-opt.picked') && [...p.doc.querySelectorAll('.q-opt')].every(b => b.disabled), 6000, 'cevap kilitlendi');
check('Cevap sonrası seçilen vurgulu, diğerleri soluk, hepsi kilitli', p.doc.querySelectorAll('.q-opt.dim').length === 2 && !!p.doc.querySelector('.q-opt.picked'));
check('"Yanıtın kaydedildi" bildirimi', stage().includes('Yanıtın kaydedildi'), stage().slice(-90));

// Sonraki soru → sonra bitir
await form(admin, `/canli/${sessionId}/sonraki`, {}, `/canli/${sessionId}`);
await waitFor(() => stage().includes('Başkent?'), 8000, 'soru 2');
check('Sonraki soru geldi, 2 seçenek', p.doc.querySelectorAll('.q-opt').length === 2 && stage().includes('2x XP'), stage().slice(0, 100));
p.doc.querySelectorAll('.q-opt')[1].click();
await waitFor(() => p.doc.querySelector('.q-opt.picked'), 6000, 'cevap 2');
await form(admin, `/canli/${sessionId}/bitir`, {}, `/canli/${sessionId}`);
await waitFor(() => stage().includes('Bitti!'), 8000, 'bitiş ekranı');
check('Bitiş ekranı: sıra ve kazanılan XP', stage().includes('Sıran: 1') && /\+\d+ XP/.test(stage()), stage());
check('Sıralamada puan görünür', /\d+ puan/.test(p.$('board').textContent), p.$('board').textContent);
const xpAfter = (await stu.http('GET', '/dashboard')).text.match(/data-count="(\d+)"/)?.[1];
check('Panelde toplam XP arttı', Number(xpAfter) > 0, 'XP=' + xpAfter);
p.close();

const errs = [...new Set([...admin.errors, ...stu.errors])];
check('JS hatası yok', errs.length === 0, errs.join(' | '));
check('PHP hatası yok', phpStderr().trim() === '', phpStderr().slice(0, 300));
const bad = summary(); stopWorker(); process.exit(bad ? 1 : 0);
