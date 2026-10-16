# v2-okul

bilisimkod.com okul/kodlama platformunun sıfırdan yeniden yazımı (Laravel 12). Analiz: `docs/SISTEM_HARITASI.md`.

## XAMPP kurulumu (C:\xampp\htdocs\v2-okul)
1. XAMPP'ta Apache + MySQL'i başlatın; phpMyAdmin'de `v2_okul` veritabanını (utf8mb4) oluşturun.
2. Bu klasörde: `composer install`
3. `copy .env.example .env` → `php artisan key:generate`
4. `php artisan migrate --seed   (avatar ve rozetler seed ile gelir)`
5. `php artisan serve` → http://127.0.0.1:8000  (veya Apache ile `public/` klasörü)
6. Giriş (**yalnızca yerel geliştirme**): `admin@school.local` / `Admin1234!`. Üretimde bu şifre kullanılmaz; bkz. `docs/DEPLOY_ALTALAN_ADI.md`

Testler: `php artisan test`
Ders görselleri için bir kez: `php artisan storage:link`

## Oyunlar
- Yerel (v2'de yazılmış): `block-grid-runner`, `keyboard-race`.
- Diğer 13 oyun, v1 paketleri `public/runner-assets/<slug>/index.html` olarak kopyalanınca iframe ile açılır;
  paket, seviye bitince `window.parent.postMessage({type:'level-complete', level, seconds, score}, location.origin)` göndermelidir (XP sunucuda verilir).
- Erişim: yönetici `/etkinlikler` sayfasından öğretmene oyun açar; öğrenci, sınıf öğretmeninin açtığı oyunları ve sınıfına verilen oyun ödevlerini oynar.

## Canlı oturumlar (Faz 5)
Tek `live_sessions` modeli: `quiz` (hız bazlı puan, 2x XP), `competition` (oyun seviyeleri; ilerleme sunucuda `game_progress`'ten türetilir),
`typing` (yazılan metin sunucuda puanlanır). Gerçek zamanlılık polling ile (1.2–1.5 sn) — socket sunucusu gerekmez, XAMPP'ta ek kurulum yok.
XP oturum bitince bir kez dağıtılır (quiz: toplanan puan; yarışlar: 1.→30, 2.→20, 3.→10, bitirenler 5, katılım 2).

## Takip, rapor, bildirim (Faz 6)
- **Canlı takip** (`/takip`): öğrenci istekleri `TrackStudentActivity` ile izlenir; son 2 dk aktif olanlar 5 sn'de bir tazelenir. Oturum kapatma `users.force_logout_at` ile. Öğretmen yalnızca kendi sınıflarını görür (`StaffScope`).
- **Analitik** (`/analitik`): toplu sorgularla başarı skoru (ödev + ders tamamlama), CSV indirme (Excel uyumlu BOM).
- **Karne** (`/rapor/{öğrenci}`): yazdır/PDF. Veli bağlantısı imzalı ve süreli (`config/parent.php`, 30 gün); girişsiz açılır.
- **Veli bildirimi**: `PARENT_CHANNEL=log` yalnızca `parent_messages`'a yazar. `whatsapp` kanalı Cloud API şablon mesajı gönderir — gerçek hesapla denenmedi, şablon parametreleri (ad, bağlantı) Meta panelindeki şablonla eşleşmeli.
- **Push/PWA**: manifest + service worker hazır. Gerçek push için: `composer require minishlink/web-push`, `php artisan push:vapid`, çıkan iki satırı `.env`'e ekleyin. Paket yoksa uygulama bildirimleri yine gelen kutusuna düşer. Push yalnızca HTTPS (veya localhost) altında çalışır.
- Zamanlayıcı: `php artisan schedule:work` (30 günden eski aktivite günlükleri her gece temizlenir).

## Kullanıcılar, toplu yükleme, QR (Faz 8)
- `/kullanicilar` (yönetici): yönetici/öğretmen hesapları; öğretmene sınıf atama `/kullanicilar/{id}/siniflar`.
- `/toplu-yukleme`: öğrenci ve öğretmen `.xlsx/.csv`. Boş şifre → otomatik 8 karakter; geçici şifreler **yalnızca yükleme sonucunda bir kez** gösterilir (yazdırın).
  `.xlsx` okumak için PHP `zip` eklentisi gerekir (XAMPP'ta varsayılan açık).
- `/giris-kartlari`: sınıf için QR'li kartlar. Kart linki önce onay sayfası açar (mesajlaşma uygulamalarının link önizlemesi kartı yakmasın diye), "Giriş yap" ile tüketilir.
- Giriş ekranındaki **QR ile giriş**: öğrenci bilgisayarında QR çıkar, öğretmen telefonundan okutup öğrenciyi seçerek onaylar. Onay sayfası isteği yapan cihazın IP/tarayıcısını gösterir; yalnızca öğrencinin kendi yanındaki cihazı onaylayın.
  QR kodu çizen kütüphane CDN'den yüklenir (internet gerekir).

## Yoklama ve notlar (Faz 9)
`/yoklama` (giriş + `/yoklama/rapor`), `/notlar` (+ `/notlar/giris`). Öğrenci: `/ogrenci/devamsizlik`, `/ogrenci/notlarim`. Ayrıntı: `docs/V1_ILE_FARKLAR.md`.

## Oyunlar (Faz 10)
Yerel oyunlar: `block-grid-runner`, `keyboard-race`, `silent-teacher-runner`, `lightbot-runner`, `web-lab-runner`.
Yeni bir doğrulamalı oyun eklemek için: `app/Games/GameVerifier` arayüzünü uygulayın, seviyeleri `config/game_levels.php`'ye ve `config/runners.php`'ye (`'verifier' => ...`) ekleyin, `resources/views/games/native/<slug>.blade.php` yazın.

## v1 derslerini aktarma (Faz 11)
`/courses` sayfasından v1 `.coursepkg` (veya `storage/app/course_*_backup.json`) dosyalarını seçin. Resimlerin görünmesi için bir kez `php artisan storage:link` çalıştırın. Rapor, taşınamayanları uyarı olarak listeler. Ayrıntı: `docs/V1_ILE_FARKLAR.md`.

## Tarayıcıya yakın testler (jsdom)
`npm install` sonra `npm run test:dom` (hızlı) veya `npm run test:dom:full`. PHPUnit'ten ayrıdır; ayrıntı ve sınırlar: `tests/dom/README.md`.

## Arı Bahçesi ve Python Editörü (Faz 13)
`php artisan storage:link` gerekmez. Python Editörü Pyodide'i CDN'den yükler; internetsiz kullanım için `.env`'de `PYODIDE_URL` (ayrıntı: `docs/V1_ILE_FARKLAR.md`). Doğrulama sınırları orada açıkça yazılıdır.

## Yayına alma
GitHub + alt alan adı adımları: `docs/DEPLOY_ALTALAN_ADI.md`. Üretim ayarları: `.env.production.example`. Güncelleme: `bash scripts/deploy.sh`.

## Arayüz / tasarım sistemi (Faz 14)
- İki kabuk: **personel** (koyu menü, sade, profesyonel) ve **öğrenci** (renkli, animasyonlu, seviye/XP çubuğu, alt menü). Giriş ekranı ve misafir sayfaları ayrı.
- Stil: `resources/css/app.css` (Tailwind v4 + tasarım dili) → **derlenmiş çıktı `public/css/app.css` depoya dahildir**, sunucuda Node gerekmez.
  CSS'e/şablonlara sınıf eklediğinizde yeniden derleyin: `npm install` (bir kez) → `npm run build:css`.
- Davranış: `public/js/ui.js` (menü, bildirim, sayaç, XP çubuğu, konfeti). JS kapalıysa sayfalar yine çalışır.
- Eski sayfalar (Tailwind yardımcı sınıflarıyla yazılmış) CSS'in sonundaki "yükseltme" kurallarıyla yeni görünüme otomatik uyar; yeni sayfalarda `.card`, `.btn`, `.badge`, `.field` (personel) ve `.s-card`, `.s-btn`, `.s-chip` (öğrenci) bileşenlerini kullanın.
