# v2-okul'u alt alan adında yayına alma (GitHub → Hostinger)

Örnek adlar: alt alan adı `okul.bilisimkod.com`, depo `dogukangurtekin/v2-okul`. Kendi seçtiklerinizle değiştirin.
v1 (`bilisimkod.com`) ile bir bağlantısı yoktur: ayrı klasör, ayrı veritabanı, ayrı `.env`.

## 0. Karar verilecekler
| Konu | Öneri |
|---|---|
| Alt alan adı | `okul.bilisimkod.com` |
| GitHub deposu | **Özel (private)** `v2-okul` — kodda yönetici şifresi yok ama sistem haritası/şema belgeleri var |
| Kod nerede durur | Web kökünün **dışında** (`~/apps/v2-okul`); alt alanın belge kökü yalnızca `public/`'i gösterir |

## 1. Alt alan adı ve veritabanı (hPanel — siz)
1. **Alan Adları → Alt Alan Adları**: `okul` oluşturun. SSL'in (Let's Encrypt) etkin olduğunu doğrulayın.
2. **Veritabanları → MySQL**: yeni veritabanı + kullanıcı oluşturun (`utf8mb4`). Ad/kullanıcı/şifreyi bir yere not edin; sohbete yazmayın.
3. **Gelişmiş → SSH erişimi** açık olmalı (bu sunucuda açık).

## 2. GitHub'a gönderme (kendi bilgisayarınız)
```
cd C:\xampp\htdocs\v2-okul
git init -b main
git add -A
git status          # .env, vendor, node_modules LİSTEDE OLMAMALI
git commit -m "v2-okul ilk sürüm"
git remote add origin git@github.com:dogukangurtekin/v2-okul.git
git push -u origin main
```
Önce GitHub'da **boş, özel** `v2-okul` deposunu oluşturun. `git status` çıktısında `.env` görürseniz durun: `.gitignore` çalışmıyor demektir.

## 3. Sunucuda kurulum (SSH)
```
mkdir -p ~/apps && cd ~/apps
git clone git@github-bilisim:dogukangurtekin/v2-okul.git      # v1 için kullandığınız SSH takma adı
cd v2-okul
cp .env.production.example .env
nano .env        # APP_URL, DB_*, ADMIN_EMAIL değerlerini doldurun (APP_DEBUG=false kalsın)
bash scripts/deploy.sh      # bağımlılık, APP_KEY, migration, önbellek
php artisan db:seed --force # ilk yönetici + avatar/rozetler (şifre bir kez yazdırılır!)
```
`db:seed` çıktısındaki yönetici şifresini hemen kaydedin ve ilk girişten sonra değiştirin. `ADMIN_PASSWORD` verirseniz rastgele şifre üretilmez.

## 4. Belge kökünü bağlama
Laravel'in web kökü `public/` olmalıdır (`.env` ve kaynak kod web'den görünmesin diye).
- **Seçenek A (önerilen):** alt alanın belge kökü klasörünü (hPanel'de gösterilen yol) boşaltıp `~/apps/v2-okul/public`'e sembolik bağ yapın:
  ```
  rmdir /yol/okul.bilisimkod.com/public_html     # boş olmalı
  ln -s ~/apps/v2-okul/public /yol/okul.bilisimkod.com/public_html
  ```
- **Seçenek B:** hPanel alt alan ayarında özel belge kökü seçeneği varsa doğrudan `public` klasörünü gösterin.
- Sembolik bağ çalışmazsa (404/403) hosting `FollowSymLinks` izni vermiyor olabilir; bana yolu söyleyin, başka bir çözüm bakarız. v1'deki gibi kök `index.php` hilesi **önerilmez** (`.env` korumasız kalabilir).

## 5. Zamanlanmış görevler
hPanel → **Gelişmiş → Cron Jobs**, her dakika:
```
cd ~/apps/v2-okul && php artisan schedule:run >> /dev/null 2>&1
```
Yoklama hatırlatması ve eski etkinlik günlüklerinin temizliği buna bağlıdır.

## 6. Yayın sonrası kontrol listesi
- [ ] `https://okul.bilisimkod.com/login` açılıyor, kilit simgesi geçerli
- [ ] Yönetici girişi çalışıyor, şifre değiştirildi
- [ ] `https://okul.bilisimkod.com/.env` → **403/404** (200 dönerse DURUN, belge kökü yanlış)
- [ ] `https://okul.bilisimkod.com/storage/logs/laravel.log` → 403/404
- [ ] Sınıf + öğrenci ekle → öğrenciyle giriş → ders/oyun
- [ ] Tarayıcı konsolunda (F12) kırmızı hata yok
- [ ] Yedek: günlük `mysqldump` (hPanel → Yedekler veya cron) ve `.env`'in güvenli kopyası

## 7. Güncelleme akışı
Kendi bilgisayarınızda değişiklik → `git commit` → `git push`. Sunucuda:
```
cd ~/apps/v2-okul && git pull --ff-only && bash scripts/deploy.sh
```
`deploy.sh` hata verirse site bakımda **kalmaz**. Geri dönmek için `git checkout <önceki-commit> && bash scripts/deploy.sh`; migration'lar kendiliğinden geri alınmaz (önce yedek alın).

## 8. Sık sorunlar
| Belirti | Neden / çözüm |
|---|---|
| 500 hatası, boş sayfa | `storage/logs/laravel.log`'a bakın; `storage` ve `bootstrap/cache` yazılabilir mi? (`chmod -R ug+rwX storage bootstrap/cache`) |
| "No application encryption key" | `php artisan key:generate --force` sonra `php artisan config:cache` |
| Resimler görünmüyor | `php artisan storage:link` (deploy.sh yapar) |
| `.env` değişikliği etkisiz | `php artisan config:cache` yeniden (önbellek `.env`'i okumaz) |
| Giriş sonrası hemen çıkış | `SESSION_SECURE_COOKIE=true` iken site http'den açılıyor; https kullanın |
