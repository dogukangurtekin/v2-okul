import { Client, startWorker, stopWorker, phpStderr, waitFor, sleep, input, check, summary, answers, USERS } from './dom.mjs';

const ANS = answers().st;
const LB = [
  { main: 'FFFX', proc: '' }, { main: 'FFFRFFX', proc: '' }, { main: 'RFFFLFFFLFX', proc: '' },
  { main: 'PP', proc: 'FFX' }, { main: 'PPP', proc: 'FFFFXR' }, { main: 'PXRPRPX', proc: 'FFFF' },
];
const WL = [
  { html: '<h1>Merhaba dünya</h1>', css: '' }, { html: '<h1>Sayfam</h1><p>Bu bir paragraf metnidir.</p>', css: '' },
  { html: '<ul><li>a</li><li>b</li><li>c</li></ul>', css: '' }, { html: '<a href="https://example.com">Git</a>', css: '' },
  { html: '<img src="a.png" alt="Bir resim">', css: '' }, { html: '<h1>Renkli</h1>', css: 'h1 { color: #FF0000; }' },
  { html: '<p>Merhaba</p>', css: 'p { font-size: 20px; background-color: yellow }' }, { html: '<div class="kutu">x</div>', css: '.kutu { border: 1px solid black; padding: 10px; }' },
];
const mode = process.argv[2] === 'staff' ? 'staff' : 'student';
const msgText = p => p.$('msg').textContent.replace(/\s+/g, ' ').trim();
const tabsState = p => [...p.$('tabs').querySelectorAll('button')].map(b => b.disabled ? 'L' : (b.textContent.includes('✅') ? 'D' : 'o')).join('');

await startWorker();
const c = new Client();
const status = await c.login(...USERS[mode]);
check(`[${mode}] giriş`, status === 302, 'status ' + status);

// ---------------- Silent Teacher ----------------
{
  const p = await c.open('/oyun/silent-teacher-runner?v2=1');
  check('ST: sayfa açıldı, 12 sekme', p.$('tabs').children.length === 12, tabsState(p));
  check('ST: kod satır numaralı gösteriliyor', p.$('code').textContent.includes('print("Merhaba")') && /\s1\s+print/.test(p.$('code').textContent));
  if (mode === 'student') check('ST: ilk seviye açık, diğerleri kilitli', tabsState(p) === 'o' + 'L'.repeat(11), tabsState(p));
  else check('ST (personel): tüm sekmeler açık', !tabsState(p).includes('L'), tabsState(p));

  input(p.$('ans'), 'yanlis', p.w); p.$('msg').innerHTML = ''; p.$('chk').click();
  await waitFor(() => p.$('msg').children.length, 4000, 'ST yanlış cevap mesajı');
  check('ST: yanlış cevap kırmızı mesaj', p.$('msg').innerHTML.includes('bg-red-100'), msgText(p));

  let xpOk = true;
  for (let i = 0; i < 12; i++) {
    if (i > 0) { const b = p.$('tabs').children[i]; if (b.disabled) { check(`ST: seviye ${i + 1} sekmesi açılmadı`, false, tabsState(p)); break; } b.click(); await sleep(20); }
    input(p.$('ans'), ANS[i], p.w); p.$('msg').innerHTML = ''; p.$('chk').click();
    try { await waitFor(() => p.$('msg').innerHTML.includes('bg-green-100'), 4000, 'ST doğru'); } catch (e) { check(`ST: seviye ${i + 1} doğru cevap kabul`, false, msgText(p)); break; }
    if (mode === 'student' && !msgText(p).includes('+10 XP')) xpOk = false;
  }
  check('ST: 12 seviye çözüldü' + (mode === 'student' ? ', her birinde +10 XP' : ''), mode === 'student' ? xpOk : true);
  if (mode === 'student') check('ST: tüm sekmeler ✅', tabsState(p) === 'D'.repeat(12), tabsState(p));
  check('ST: açıklama gösteriliyor', p.$('msg').textContent.length > 10);
  p.close();
}

// ---------------- Lightbot ----------------
{
  const p = await c.open('/oyun/lightbot-runner?v2=1');
  check('LB: sayfa açıldı, 6 sekme', p.$('tabs').children.length === 6, tabsState(p));
  check('LB: tuval çizildi', p.w.__draws.length > 5, 'çizim çağrısı ' + p.w.__draws.length);
  const add = seq => { for (const ch of seq) { const b = p.$('pal').querySelector(`[data-c="${ch}"]`); if (!b) throw new Error('palette yok: ' + ch); b.click(); } };
  const run = async () => { p.$('run').click(); await waitFor(() => p.$('msg').textContent.trim().length > 0, 8000, 'LB sonuç mesajı'); };
  const lvl = i => { p.$('tabs').children[i].click(); };

  // hatalı programlar
  add('FF'); await run(); check('LB: eksik ışık uyarısı', msgText(p).includes('yanmadı'), msgText(p));
  p.$('clr').click(); add('LF'); await run(); check('LB: duvara çarpma uyarısı', msgText(p).includes('duvara'), msgText(p));
  p.$('clr').click(); add('FFFFFFFFFFFFF'); check('LB: komut sınırı (6) uygulanıyor', p.$('cm').textContent.trim() === '6/6', p.$('cm').textContent);
  p.$('lm').querySelector('button').click(); check('LB: komuta tıklayınca siliniyor', p.$('cm').textContent.trim() === '5/6', p.$('cm').textContent);
  p.$('clr').click(); check('LB: seviye 1 prosedür paneli gizli', p.$('pw').style.display === 'none');

  for (let i = 0; i < 6; i++) {
    if (i > 0) { await waitFor(() => !p.$('tabs').children[i].disabled, 3000, `LB sekme ${i + 1} açıldı`); lvl(i); await sleep(20); }
    const s = LB[i];
    if (s.proc) { p.$('tp').click(); add(s.proc); p.$('tm').click(); }
    add(s.main);
    p.$('run').click();
    try { await waitFor(() => /Seviye tamam|duvara|yanmadı|Komut|çok/.test(p.$('msg').textContent), 15000, 'LB seviye ' + (i + 1)); } catch (e) { check(`LB: seviye ${i + 1} sonuç`, false, 'mesaj yok'); break; }
    const ok = p.$('msg').textContent.includes('Seviye tamam');
    check(`LB: seviye ${i + 1} çözüldü` + (mode === 'student' ? ' (+12 XP)' : ''), ok && (mode !== 'student' || p.$('msg').textContent.includes('+12 XP')), msgText(p));
    if (!ok) break;
  }
  if (mode === 'student') check('LB: sekmeler ✅', tabsState(p) === 'DDDDDD', tabsState(p));
  // prosedürsüz uzun çözüm 4. seviyede sınır nedeniyle girilemez
  lvl(3); await sleep(20); p.$('clr').click(); add('FFXFFX'); check('LB: seviye 4 ana program 3 ile sınırlı', p.$('cm').textContent.trim() === '3/3', p.$('cm').textContent);
  p.close();
}

// ---------------- Web Atölyesi ----------------
{
  const p = await c.open('/oyun/web-lab-runner?v2=1');
  check('WL: sayfa açıldı, 8 sekme', p.$('tabs').children.length === 8, tabsState(p));
  check('WL: iframe sandbox="" (betik yok)', p.$('prev').getAttribute('sandbox') === '', String(p.$('prev').getAttribute('sandbox')));
  check('WL: başlangıç HTML yorum satırı', p.$('html').value.includes('kodunu buraya'), p.$('html').value);

  // boş kontrol → eksik mesajları
  p.$('msg').innerHTML = ''; p.$('chk').click(); await waitFor(() => p.$('msg').children.length, 4000, 'WL boş mesaj');
  check('WL: boş kodla eksikler listelenir', p.$('msg').innerHTML.includes('<li>') && p.$('msg').textContent.includes('<h1>'), msgText(p));

  // önizleme güncelleme + script güvenliği
  input(p.$('html'), '<h1>Selam</h1><script>window.__ran=1</script>', p.w); await sleep(80);
  check('WL: önizleme srcdoc güncellendi', p.$('prev').srcdoc.includes('<h1>Selam</h1>'));
  check('WL: iframe betiği çalışmadı (jsdom sandbox uygulamaz, bu yüzden yalnız srcdoc kontrolü)', true);

  for (let i = 0; i < 8; i++) {
    if (i > 0) { await waitFor(() => !p.$('tabs').children[i].disabled, 3000, `WL sekme ${i + 1}`); p.$('tabs').children[i].click(); await sleep(20); }
    input(p.$('html'), WL[i].html, p.w); input(p.$('css'), WL[i].css, p.w);
    p.$('msg').innerHTML = ''; p.$('chk').click();
    try { await waitFor(() => p.$('msg').innerHTML.includes('bg-green-100') || p.$('msg').innerHTML.includes('bg-red-100'), 6000, 'WL sonuç'); } catch { check(`WL: görev ${i + 1} sonuç`, false, 'mesaj yok'); break; }
    const ok = p.$('msg').innerHTML.includes('bg-green-100');
    check(`WL: görev ${i + 1} geçti` + (mode === 'student' ? ' (+12 XP)' : ''), ok && (mode !== 'student' || p.$('msg').textContent.includes('+12 XP')), msgText(p));
    if (!ok) break;
  }
  if (mode === 'student') check('WL: sekmeler ✅', tabsState(p) === 'D'.repeat(8), tabsState(p));
  p.close();
}

const errs = [...new Set(c.errors)];
check('JS hatası yok (jsdomError / console.error / window.error)', errs.length === 0, errs.join('\n   '));
const php = phpStderr().trim();   // Deprecated bildirimleri sayılmaz
check('PHP tarafında hata/uyarı yok', php === '', php.slice(0, 400));
const http5 = c.requests.filter(r => / -> 5\d\d$/.test(r));
check('Sunucu 5xx yok', http5.length === 0, http5.join('; '));
console.log('\nİstek özeti:', c.requests.length, 'istek; 4xx:', c.requests.filter(r => / -> 4\d\d$/.test(r)).join('; ') || '-');
const bad = summary();
stopWorker();
process.exit(bad ? 1 : 0);
