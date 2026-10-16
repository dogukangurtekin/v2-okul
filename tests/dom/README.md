# tests/dom — tarayıcıya yakın (jsdom) oyun testleri

Oyun sayfalarının satır içi JavaScript'ini **gerçek Laravel yanıtlarıyla** çalıştırır: sunucu açmaya gerek yok;
`bridge.php` Laravel çekirdeğini doğrudan çağırır, `fetch()` buraya bağlanır. Öğrenci ve yönetici olarak giriş yapar,
Silent Teacher / Lightbot / Web Atölyesi'ni tıklayıp çözer, XP ve seviye kilitlerini doğrular.

## Kurulum (bir kez)
```
composer install
npm install            # jsdom dahil
```
Windows/XAMPP'ta PHP yolu PATH'te değilse (aynı komut penceresinde):
```
set PHP_BIN=C:\xampp\php\php.exe
```
PHP'de `pdo_sqlite` açık olmalı (XAMPP'ta varsayılan olarak açıktır).

## Çalıştırma
```
npm run test:dom         # öğrenci + personel akışı + Python worker + yeniden yükleme (≈3 dk)
npm run test:dom -- --only=games2   # yalnızca adı eşleşen takım
npm run test:dom:full    # + Lightbot istemci/sunucu fuzz'ı (≈2–3 dk, 226 rastgele program)
```
Her takımdan önce `tests/dom/.cache/test.sqlite` **sıfırdan** kurulur. `.env` ve gerçek veritabanınıza dokunulmaz;
`seed.php`, yalnızca `.cache` içindeki sqlite dosyasında çalışır ve başka her durumda reddeder.

## Ne yakalar
- Sayfa betiklerindeki söz dizimi/çalışma zamanı hataları (`jsdomError`, `window.error`, `console.error`, yakalanmamış promise)
- Bozuk DOM kimlikleri, tıklama/giriş akışlarının kopması, sekme kilidi ve ✅ durumları
- Sunucu 5xx ve PHP uyarıları (Deprecated hariç)
- Lightbot'ta **tarayıcı simülasyonu ile PHP doğrulayıcısının aynı kararı vermesi**
- Cevap anahtarının sayfaya sızmaması (PHPUnit `VerifiedGamesTest` ile birlikte)

## Ne YAKALAMAZ (gerçek tarayıcı gerekir)
CSS/Tailwind yerleşimi ve görünüm, tuvalin gerçek piksel çıktısı (yalnızca çizim çağrıları sayılır),
`<iframe sandbox>`'ın uygulanması (jsdom sandbox uygulamaz), tarayıcıya özgü davranışlar, CDN betiklerinin yüklenmesi
(Tailwind/QR kütüphanesi bu testte yüklenmez), tarayıcıdaki gerçek `Worker`/`Blob` yolu ve Arı Bahçesi önizlemesinin gerçek Flexbox yerleşimi. Yayından önce oyunları bir kez gerçek tarayıcıda elle açın.

## Dosyalar
| Dosya | Görev |
|---|---|
| `run.mjs` | orkestratör: PHP/vendor kontrolü → her takımdan önce seed → takımı çalıştır |
| `dom.mjs` | jsdom + köprü donanımı (`Client`, `check`, `waitFor`…) |
| `bridge.php` | kalıcı PHP işçisi: JSON istek → Laravel → JSON yanıt |
| `seed.php` | korumalı test veritabanı kurulumu (`ali@x.test`, `t@x.test`, yönetici) |
| `games.mjs` | üç oyunun tam akışı (`student` / `staff` argümanı) |
| `live.mjs` | Canlı quiz: yönetici + öğrenci oturumu, öğrenci oyun sayfasının (Kahoot tarzı) betiği gerçek sunucuyla |
| `persist.mjs` | yeniden yükleme, seviye atlama reddi, tekrar XP yok, panel XP toplamı |
| `games2.mjs` | Arı Bahçesi ve Python Editörü akışları (Python **gerçek Pyodide** ile Node içinde çalışır) |
| `pyworker.mjs` | Python Web Worker katmanı (`pyWorkerSource`) gerçek Pyodide ile `worker_threads` içinde; zaman aşımı/yeniden başlatma mantığı |
| `pyrunner.mjs` | sayfaya enjekte edilen Node tabanlı Pyodide çalıştırıcı |
| `fuzz.mjs` | Lightbot: rastgele + çözüm mutasyonları, istemci ↔ sunucu uyumu |

Yeni oyun testi eklerken `games.mjs`'teki bloklardan birini kopyalayın; yardımcılar `dom.mjs`'tedir.
