/* Arı Bahçesi (CSS) ve Python Editörü sayfa akışları. Argüman: student | staff */
import { Client, startWorker, stopWorker, phpStderr, waitFor, sleep, input, check, summary, USERS } from './dom.mjs';
import { makeRunner } from './pyrunner.mjs';

const mode = process.argv[2] === 'staff' ? 'staff' : 'student';
const BEE = [
  ['#garden { display: flex; justify-content: flex-end; }', 1], ['#garden { display: flex; justify-content: center; }', 1],
  ['#garden { display: flex; align-items: flex-end; }', 1], ['#garden { display: flex; justify-content: center; align-items: center; }', 1],
  ['#garden { display: flex; justify-content: space-between; }', 3],
  ['#garden { display: flex; flex-direction: row-reverse; justify-content: flex-start; align-items: center; }', 2],
  ['#garden { display: flex; flex-direction: column; justify-content: space-around; align-items: flex-end; }', 3],
  ['#garden { display: flex; flex-direction: column-reverse; justify-content: flex-end; }', 2],
];
const PY = [
  'print("Merhaba Python")', 'yas = 12\nprint("Yaşım:", yas)', 'print(7 * 8 + 2)',
  'sayi = 17\nif sayi % 2 == 1:\n    print("Tek")\nelse:\n    print("Çift")', 'for i in range(1, 6):\n    print(i)',
  'toplam = 0\nfor i in range(1, 11):\n    toplam += i\nprint(toplam)', 'def toplam(a, b):\n    return a + b\n\nprint(toplam(3, 4))',
  'n = 3\nwhile n > 0:\n    print(n)\n    n -= 1\nprint("Başla")',
];
const tabsState = p => [...p.$('tabs').querySelectorAll('button')].map(b => b.disabled ? 'L' : (b.textContent.includes('✅') ? 'D' : 'o')).join('');
const msgText = p => p.$('msg').textContent.replace(/\s+/g, ' ').trim();
const count = (s, needle) => s.split(needle).length - 1;

await startWorker();
const c = new Client();
check(`[${mode}] giriş`, (await c.login(...USERS[mode])) === 302);

// ---------------- Arı Bahçesi ----------------
{
  const p = await c.open('/oyun/bee-garden-runner?v2=1');
  check('BG: sayfa açıldı, 8 sekme', p.$('tabs').children.length === 8, tabsState(p));
  check('BG: iframe sandbox="" (betik yok)', p.$('prev').getAttribute('sandbox') === '');
  mode === 'student' ? check('BG: yalnızca ilk seviye açık', tabsState(p) === 'o' + 'L'.repeat(7), tabsState(p)) : check('BG (personel): tüm sekmeler açık', !tabsState(p).includes('L'), tabsState(p));
  check('BG: başlangıç CSS #garden + display:flex', p.$('css').value.includes('#garden') && p.$('css').value.includes('display: flex'));
  await sleep(40);
  let doc = p.$('prev').srcdoc;
  check('BG: önizleme 1 arı + 1 çiçek içeriyor', count(doc, 'class="bee"') === 1 && count(doc, 'class="flower"') === 1, doc.slice(0, 120));
  check('BG: önizlemede sabitleme kuralları (!important) var', doc.includes('flex-wrap:nowrap!important') && doc.includes('.bee{width:60px!important'));
  check('BG: çiçek 1. seviyede sağ üstte (left:240px;top:0px)', doc.includes('left:240px;top:0px'), '');

  p.$('msg').innerHTML = ''; p.$('chk').click();      // başlangıç şablonu: çözüm değil
  await waitFor(() => p.$('msg').children.length, 4000, 'BG yanlış mesaj');
  check('BG: şablon haliyle red + ipucu', p.$('msg').innerHTML.includes('bg-red-100') && msgText(p).includes('İpucu'), msgText(p));

  input(p.$('css'), '.bee { margin: 5px }', p.w); p.$('msg').innerHTML = ''; p.$('chk').click();
  await waitFor(() => p.$('msg').children.length, 4000, 'BG yabancı seçici');
  check('BG: #garden dışı seçici reddedilir', msgText(p).includes('Yalnızca #garden'), msgText(p));

  input(p.$('css'), '</style><script>alert(1)</script>#garden{display:flex}', p.w); await sleep(60);
  check('BG: </style> enjeksiyonu önizlemede etkisiz', !p.$('prev').srcdoc.includes('</style><script>'), '');

  for (let i = 0; i < 8; i++) {
    if (i > 0) { await waitFor(() => !p.$('tabs').children[i].disabled, 3000, `BG sekme ${i + 1}`); p.$('tabs').children[i].click(); await sleep(30); }
    input(p.$('css'), BEE[i][0], p.w); await sleep(60);
    doc = p.$('prev').srcdoc;
    const shown = count(doc, 'class="bee"') === BEE[i][1] && count(doc, 'class="flower"') === BEE[i][1] && doc.includes(BEE[i][0]);
    check(`BG: seviye ${i + 1} önizleme ${BEE[i][1]} arı/çiçek + yazılan CSS`, shown, `bee=${count(doc, 'class="bee"')} flower=${count(doc, 'class="flower"')}`);
    p.$('msg').innerHTML = ''; p.$('chk').click();
    try { await waitFor(() => p.$('msg').innerHTML.includes('bg-green-100') || p.$('msg').innerHTML.includes('bg-red-100'), 6000, 'BG sonuç'); } catch { check(`BG: seviye ${i + 1} sonuç`, false, 'mesaj yok'); break; }
    const ok = p.$('msg').innerHTML.includes('bg-green-100');
    check(`BG: seviye ${i + 1} çözüldü` + (mode === 'student' ? ' (+12 XP)' : ''), ok && (mode !== 'student' || msgText(p).includes('+12 XP')), msgText(p));
    if (!ok) break;
  }
  if (mode === 'student') check('BG: sekmeler ✅', tabsState(p) === 'D'.repeat(8), tabsState(p));
  p.close();
}

// ---------------- Python Editörü (gerçek Pyodide, Node içinde) ----------------
{
  const runner = makeRunner();
  const p = await c.open('/oyun/python-editor?v2=1', { beforeParse: w => { w.PyRunner = runner; } });
  const run = async (code, stdin = '') => { input(p.$('code'), code, p.w); input(p.$('stdin'), stdin, p.w); p.$('out').textContent = '\u0000'; p.$('run').click(); await waitFor(() => p.$('out').textContent !== '\u0000' && !p.$('run').disabled, 90000, 'Python çalıştırma'); return p.$('out').textContent; };
  const submit = async () => { p.$('msg').innerHTML = ''; p.$('submit').click(); await waitFor(() => p.$('msg').children.length, 6000, 'gönder sonucu'); return msgText(p); };

  check('PY: sayfa açıldı, 8 görev sekmesi + Serbest düğmesi', p.$('tabs').children.length === 8 && !!p.$('free'), tabsState(p));
  check('PY: sayfa window.PyRunner\'ı kullanıyor (Worker oluşturmadı)', true);
  mode === 'student' ? check('PY: yalnızca ilk görev açık', tabsState(p) === 'o' + 'L'.repeat(7), tabsState(p)) : check('PY (personel): tüm sekmeler açık', !tabsState(p).includes('L'));
  check('PY: başlangıç kodu yüklendi', p.$('code').value.includes('# kodunu buraya yaz'));

  check('PY: çalıştırmadan gönder → uyarı', (await submit()).includes('Önce kodunu'), msgText(p));
  let out = await run('print("x")');
  check('PY: çalıştır → çıktı gösterilir', out === 'x\n', JSON.stringify(out));
  check('PY: yanlış çıktıyı gönder → sunucu reddeder', (await submit()).includes('beklenenle aynı değil'), msgText(p));
  p.$('code').value = 'print("x")  # düzenlendi'; check('PY: çalıştırdıktan sonra kod değişirse gönder → uyarı', (await submit()).includes('Önce kodunu'), msgText(p));

  out = await run('print(1)\n1/0');
  check('PY: hata → traceback (ZeroDivisionError) kırmızı gösterilir', out.includes('ZeroDivisionError') && p.$('out').style.color !== '', out.slice(-80));
  check('PY: hatalı çalıştırmadan sonra gönder → uyarı', (await submit()).includes('Önce kodunu'), msgText(p));
  out = await run('def f(:\n  pass');
  check('PY: söz dizimi hatası gösterilir', out.includes('SyntaxError'), out.slice(-80));

  // Ctrl+Enter ve Tab
  input(p.$('code'), 'print("kısayol")', p.w); p.$('out').textContent = '';
  p.$('code').dispatchEvent(new p.w.KeyboardEvent('keydown', { key: 'Enter', ctrlKey: true, bubbles: true, cancelable: true }));
  await waitFor(() => p.$('out').textContent === 'kısayol\n', 60000, 'Ctrl+Enter');
  check('PY: Ctrl+Enter çalıştırır', true);
  p.$('code').value = 'if x:\n'; p.$('code').setSelectionRange(p.$('code').value.length, p.$('code').value.length);
  const tab = new p.w.KeyboardEvent('keydown', { key: 'Tab', bubbles: true, cancelable: true }); p.$('code').dispatchEvent(tab);
  check('PY: Tab 4 boşluk ekler ve odağı bırakmaz', p.$('code').value === 'if x:\n    ' && tab.defaultPrevented, JSON.stringify(p.$('code').value));

  // Serbest mod
  const reqBefore = c.requests.length;
  p.$('free').click();
  check('PY: Serbest modda görev kutusu ve Gönder gizli', p.$('taskbox').style.display === 'none' && p.$('submit').style.display === 'none');
  out = await run('ad = input("Ad? ")\nprint(ad.upper())', 'ali\n');
  check('PY: input() girdi kutusundan okunur', out === 'Ad? ALI\n', JSON.stringify(out));
  out = await run('print("sonsuz değil", end="")');
  check('PY: satır sonu olmayan çıktı kaybolmaz', out === 'sonsuz değil', JSON.stringify(out));
  out = await run('print("iki")');
  check('PY: önceki çalıştırmanın artığı sızmaz', out === 'iki\n', JSON.stringify(out));
  check('PY: serbest mod sunucuya hiçbir şey göndermedi', !c.requests.slice(reqBefore).some(r => r.startsWith('POST') && !r.includes('/sure/ping')), c.requests.slice(reqBefore).join(';'));   // 30 sn'lik etkin süre ping'i hariç

  p.$('tabs').children[0].click(); await sleep(30);      // görev moduna dön
  // Tüm görevler
  for (let i = 0; i < 8; i++) {
    if (i > 0) { await waitFor(() => !p.$('tabs').children[i].disabled, 3000, `PY sekme ${i + 1}`); p.$('tabs').children[i].click(); await sleep(30); }
    if (i === 2) {                                           // sabit yazma → yapı denetimi
      await run('print(58)'); const m = await submit();
      check('PY: seviye 3 sabit cevap (print(58)) sunucuda reddedilir', p.$('msg').innerHTML.includes('bg-red-100') && m.length > 0 && !m.includes('XP'), m);
    }
    await run(PY[i]);
    const m = await submit();
    const ok = p.$('msg').innerHTML.includes('bg-green-100');
    check(`PY: görev ${i + 1} çözüldü` + (mode === 'student' ? ' (+10 XP)' : ''), ok && (mode !== 'student' || m.includes('+10 XP')), m);
    if (!ok) break;
  }
  if (mode === 'student') check('PY: sekmeler ✅', tabsState(p) === 'D'.repeat(8), tabsState(p));
  check('PY: çalıştırıcı gerçekten çağrıldı', runner.calls >= 16, 'çağrı ' + runner.calls);
  p.close();
}

const errs = [...new Set(c.errors)];
check('JS hatası yok (jsdomError / console.error / window.error)', errs.length === 0, errs.join('\n   '));
const php = phpStderr().trim();
check('PHP tarafında hata/uyarı yok', php === '', php.slice(0, 400));
const http5 = c.requests.filter(r => / -> 5\d\d$/.test(r));
check('Sunucu 5xx yok', http5.length === 0, http5.join('; '));
const bad = summary(); stopWorker(); process.exit(bad ? 1 : 0);
