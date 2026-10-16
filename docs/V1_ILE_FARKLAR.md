# v2-okul — v1'den taşınmayanlar ve bilinçli farklar

## Bilinçli farklar (tasarım kararı)
| Konu | v1 | v2 |
|---|---|---|
| XP | `user_profiles.xp`, `student_reports.total_xp`, `content_progresses`, `user_xp_logs`, `student_xp_grants`… | Tek defter `xp_ledger` + `students.xp_balance/xp_total` (`XpService`) |
| Oda/oturum | `live_quiz_sessions`, `competition_rooms`, `rooms`, `coding_live_quiz_rooms` | Tek `live_sessions` (quiz / competition / typing) |
| Ödev | `course_homeworks` + `game_assignments` | Tek `assignments` (+ `assignment_submissions`) |
| Ders içeriği | `courses.lesson_payload` JSON blob | `course_slides` satırları |
| Öğrenci şifresi | `student_credentials.plain_password` (düz metin) | Yok; yalnızca hash |
| Silme | GET ile silme uçları | Yalnızca POST/DELETE |
| Gerçek zamanlı | socket.io + polling | Yalnızca polling (ek servis yok) |
| Sınıf panosu | Hazır mesaj | Aynı (serbest metin yok) |

## v1'de olup v2'ye henüz taşınmayanlar
- 8 oyun: Block 3D, Compute It, Line Trace, Flamestone, 3D Grid Tasarım, Noktaları Birleştir, Kod Kartları, Flowchart. Bunlar `public/runner-assets/<slug>/` paket mekanizmasıyla bağlanır; yazılmaları bekliyor.
- Noktaları Birleştir seviye editörü, Flamestone editörü, Block Builder Studio kayıtları
- `api/client/*` doküman deposu (harici istemciler için) ve `api/v1/*` REST
- dompdf ile sunucuda PDF (v2: tarayıcıdan yazdır/PDF)
- Docker / CI dosyaları

## Sonradan taşınanlar (Faz 8)
- Kullanıcı yönetimi: yönetici/öğretmen CRUD, şifre sıfırlama, öğretmene sınıf atama (seçerek veya kademeye göre), seçili öğrencileri toplu silme (yalnız POST).
- Toplu yükleme: `.xlsx` ve `.csv` (Türkçe Excel `;` ve Windows-1254 dahil), 1000 satır sınırı, satır bazlı hata raporu. Şablon CSV olarak indirilir (gerçek `.xlsx` şablon üretilmez).
- QR giriş: (1) personelin bastığı tek kullanımlık, 7 gün geçerli **giriş kartları**; (2) giriş ekranında QR → **öğretmen onayıyla** giriş. v1'den fark: şifre kartlara basılmaz; tokenlar veritabanında yalnız hash olarak tutulur.
- Hâlâ taşınmayanlar: toplu şifre sıfırlama (yerine tek tek geçici şifre), "tüm öğrencileri sil".

## Yoklama ve not (Faz 9)
v1'de bu iki modülün yalnızca model/policy/servis iskeleti vardı, **ekranı ve rotası yoktu** (veriler analitik ve raporlarda okunuyordu). v2'de tam arayüzle yazıldı:
- **Yoklama** (`/yoklama`): sınıf + tarih (+ isteğe bağlı ders) için toplu giriş, "hepsi geldi" kısayolu, güncelleme, devamsızlık raporu ve CSV. Gelecek tarih ve 1 yıldan eski kayıt reddedilir. v1'deki `unique(student, course, date)` `course_id` boşken tekrarlara izin veriyordu; v2'de `course_key` (0 = genel) ile düzeltildi.
- **Devam oranı** = (geldi + geç) / (kayıtlar − izinli).
- **Not** (`/notlar`): sınıf için toplu not girişi (virgüllü ondalık, boş = girilmedi), dönem/tür filtresi, düzenleme/silme, **ağırlıklı ortalama** (`config/school.php`: küçük sınav 1, proje 2, yazılı 2, dönem sonu 3). Dönem otomatik: Eylül–Ocak 1., Şubat–Ağustos 2. dönem.
- **Bilinçli fark:** v1'de not puanı otomatik XP oluyordu. v2'de bu varsayılan **kapalı** (`GRADE_XP=true` ile açılır; güncellemede yalnız fark eklenir, silmede geri alınmaz).
- Öğrenci kendi devamsızlığını (`/ogrenci/devamsizlik`) ve notlarını (`/ogrenci/notlarim`) görür; karne, veli raporu ve analitik devam % ile not ortalamasını içerir.
- `php artisan attendance:remind`: o gün yoklaması girilmemiş sınıfların öğretmenlerine günde bir bildirim (hafta içi 10:00, `ATTENDANCE_REMINDER_AT`). Zamanlayıcı için `php artisan schedule:work`.
- Öğretmen yalnızca kendi sınıflarının yoklama/notunu görür ve düzenler; yönetici hepsini.

## Oyunlar (Faz 10)
Yazılan 5 yerel oyun: Blok Kodlama, Klavye Yarışması, **Silent Teacher (Python çıktı tahmini, 12 seviye)**, **Lightbot (2B, prosedürlü, 6 seviye)**, **Web Atölyesi (HTML/CSS, 8 görev)**.
- **Bunlar v1 oyunlarının kopyası değil**: v1 paketlerinin kaynağını okumadan, aynı adlarla kendi tasarımımla yazıldı; seviye içeriği ve kuralları farklı olabilir.
- **Sunucu doğrulaması (v1'den fark):** v1'de oyun sonucunu istemci bildirirdi, yani XP sahtecilikle alınabilirdi. Yeni üç oyunda istemci "bitirdim" demez, çözümünü gönderir; sunucu doğrular (`app/Games/*Verifier`). Cevap anahtarı, beklenen çıktı ve kontrol mesajları tarayıcıya hiç gönderilmez.
  Blok Kodlama ve Klavye hâlâ istemci bildirimine güveniyor (sunucuda doğrulayıcı yok).
- Web Atölyesi'nde JavaScript yok: önizleme `sandbox=""` iframe'dedir ve öğrenci kodu sunucuda yalnızca ayrıştırılır (çalıştırılmaz, saklanmaz). v1 sürümünde JS olabilir.
- Lightbot kuralları: robot doğuya bakarak başlar; duvara/kenara çarpmak programı bitirir; prosedür içinde prosedür çağrılamaz; komut sınırları seviyeye göre sunucuda zorlanır (ör. 4. seviye prosedürsüz çözülemez).
- Personel (yönetici/öğretmen) oyunları önizleyip çözümünü kontrol edebilir (`/oyun/{slug}/dene`); bu XP veya ilerleme yazmaz.
- Silent Teacher'ın 12 beklenen çıktısı gerçek Python ile çalıştırılarak doğrulandı.

## Arı Bahçesi ve Python Editörü (Faz 13)
Yerel oyun sayısı 7: Blok Kodlama, Klavye, Silent Teacher, Lightbot, Web Atölyesi, **Arı Bahçesi**, **Python Editörü**. İkisi de v1 paketlerinin kaynağı okunmadan, aynı adlarla yeniden tasarlandı.

**Arı Bahçesi (CSS / Flexbox, 8 seviye)**
- Öğrenci `#garden` için CSS yazar; önizleme tarayıcının **gerçek** Flexbox'ını kullanır (arı/kutu boyutları `!important` ile sabitlenir). Sunucu CSS'i ayrıştırıp küçük bir Flexbox modeliyle (`app/Games/FlexModel.php`) yerleşimi hesaplar ve referans çözümle karşılaştırır; aynı yerleşime götüren farklı CSS'ler kabul edilir (ör. tek arıda `center` = `space-around`).
- Model CSS gibi davranır (son kural kazanır, `!important`, geçersiz değer yok sayılır, `flex-flow` kısayolu sıraya duyarlı). Modelin hesaplayamadığı **geçerli** değerler (`start`, `end`, `place-items`, `var()`...) sessizce yok sayılmaz, açıkça reddedilir; `#garden` dışında seçici ve `@media` yazılamaz.
- **Sınır:** model, elle hesaplanmış koordinatlarla (tests/Feature/BeeGardenPythonTest) doğrulandı; gerçek bir tarayıcı motoruyla karşılaştırılmadı. Kısıtlamalar farkı en aza indirir ama sıfırlamaz.

**Python Editörü (8 görev + serbest mod)**
- Python **tarayıcıda** çalışır (Pyodide/WebAssembly, Web Worker içinde; sonsuz döngüde 8 sn sonra worker öldürülüp yeniden kurulur). `input()` için girdi kutusu var.
- **Sunucu kodu yeniden çalıştırmaz** (öğrenci kodunu sunucuda çalıştırmak güvenli değil). İstemcinin bildirdiği çıktıyı beklenenle karşılaştırır ve kodun yapısını denetler (zorunlu yapılar yorum/metin dışında aranır; cevabı doğrudan yazan sabitler reddedilir). Bu kazara/tembel atlamayı engeller, **kararlı bir hileyi engellemez** — Silent Teacher/Lightbot/Web Atölyesi/Arı Bahçesi'ndeki tam doğrulama gibi değildir.
- Pyodide ilk açılışta ≈10 MB indirir ve varsayılan olarak CDN'den gelir (internet gerekir). İnternetsiz okul için dosyaları `public/vendor/pyodide/` altına koyup `.env`'de `PYODIDE_URL=/vendor/pyodide/` verin.
- Doğrulama: 8 çözümün çıktıları gerçek Pyodide ile (Node içinde) çalıştırıldı; worker katmanı (`pyWorkerSource`) gerçek Pyodide ile Node `worker_threads` içinde sınandı (UTF-8, satır sonsuz çıktı, stdin/EOF, hata, ad alanı izolasyonu, zaman aşımı mantığı). **Gerçek tarayıcıdaki Worker/Blob yolu denenmedi.**

## v1 `.coursepkg` içe aktarma (Faz 11)
`/courses` → **İçe aktar**: tek veya çok dosya; v1 `.coursepkg` (COURSEPKG2 multipart), v1 yedek JSON'ı ve v2 JSON paketi otomatik ayrılır. Dönüştürme **v1'in kendi ürettiği 6 pakette ve gerçek `course_253_backup.json` yedeğinde** denendi (7 ders, 77 slayt, v1 slayt sayıları birebir tuttu).

| v1 | v2'de |
|---|---|
| slayt içeriği (HTML) | temizlenmiş HTML (`html`) + düz metin yedeği (`body`) |
| gömülü resim (PNG/JPEG/GIF/WebP data URL) | dosyaya çıkarılır (`storage/slides/...`), tekrarlar tek dosya |
| gömülü **SVG** resim (v1 üretici betikleri çok kullanıyor) | dosyaya **yazılmaz**; katı doğrulamadan geçerse `<img>` içinde güvenli data URL olarak saklanır |
| `image_url` / `layout_meta` resimleri, video, kod, ek dosya, yönerge | resim/video/kod slaytı; YouTube/Vimeo gömülü oynatıcı; ek dosya bağlantı; yönerge kutusu |
| soru içeren slayt | içerik slaydı (anlamlı ek metin varsa) + ayrı **soru slaydı** |
| çoktan seçmeli, doğru/yanlış | `choice` (tek doğru cevap) |
| kısa cevap | `short` (birden çok kabul edilen cevap, `|` ile) — v2'ye yeni eklendi |
| eşleştirme, sürükle-bırak | `matching` — v2'ye yeni eklendi |
| alt dersler | alt ders (`parent_id`) |
| `curriculum`, `category`, `difficulty`, açıklama | yeni `courses` sütunları; kazanımlar oynatıcıda gösterilir |
| kapak (pakette ikili parça ya da data URL) | kapak dosyası |

**Kayıplar ve davranışlar (hepsi içe aktarma raporunda uyarı olarak görünür):**
- Slayt yerleşimi (bölünmüş ekran), arka plan gradyanları, `xp/points/time_limit/double_points` v2'de yok: içerik ve resimler korunur, görünüm sadedir. (`content.v1` altında künye olarak saklanır, kullanılmaz.)
- Birden çok doğru şıklı soru → ilki alınır. Doğru şık işaretsiz soru → ilk şık varsayılır, **uyarı verir; kontrol edin**.
- `checklist` gibi desteklenmeyen soru türleri etkileşimsiz atlanır. Boş slaytlar atlanır.
- v1 sunucusundaki dosya yolları (`kapak-gorseli/..`, `uploads/..`) paketle gelmediği için taşınamaz: resim/ek dosya atlanır, kapak elle yüklenir.
- Ders kodu çakışırsa `-2` eklenir; mevcut ders asla ezilmez. Öğretmen/sınıf atamaları taşınmaz (v1 kimlikleri anlamsız); sınıf ataması elle yapılır.
- Soru cevapları oynatıcı sayfasının kaynağında okunabilir (v1'de de böyleydi); bu sorular XP vermez, yalnızca pekiştirmedir.
- Yüklenen HTML beyaz listeyle temizlenir: `<script>`, `<iframe>`, olay özellikleri (`onclick`...), `javascript:` bağlantıları ve riskli CSS atılır.
- Düzenleyicide bir slaytın metnini değiştirirseniz içe aktarılan zengin HTML atılır (düz metin kalır); metne dokunmazsanız korunur.
- Sınır: dosya başına 20 MB, tek seferde 20 dosya.

## Üretime çıkmadan önce
1. Hukuk metinlerini (`resources/views/legal/*`) okul bilgileriyle tamamlatın.
2. `.env`: `APP_DEBUG=false`, güçlü `APP_KEY`, gerçek DB şifresi, HTTPS.
3. Varsayılan yönetici şifresini (`Admin1234!`) ilk girişte değiştirin.
4. Push için VAPID anahtarları; veli WhatsApp kanalı için Meta şablonu.
