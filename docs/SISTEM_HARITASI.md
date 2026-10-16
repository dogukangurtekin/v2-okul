# bilisimkod.com — Sistem Haritası (v1 analizi)

Kaynak: sunucudaki Laravel 12 projesi (PHP 8.2, ~17.400 satır controller/service kodu, 331 rota, 78 migration, ~70 model).
Amaç: v2-okul bu sistemin **mantığını** sıfırdan, daha temiz kurar.

## 1. Ürün tanımı
Türkçe, K-12 bilişim/kodlama öğretimi için **okul yönetim + oyunlaştırılmış öğrenme platformu**.
Öğretmen ders/ödev/etkinlik atar; öğrenci ders slaytlarını izler, kodlama oyunları oynar, XP kazanır, avatar/rozet toplar; veli rapor alır.

## 2. Teknoloji (v1)
| Katman | Kullanılan |
|---|---|
| Backend | Laravel 12, Blade, session auth, policy + `role` middleware |
| Mimari | Controller + Service (+ Repository: `app/Repositories`) |
| DB | MySQL (prod), SQLite (dev); JSON kolonlar yoğun |
| Frontend | Blade + vanilla JS, Vue (BlockBuilder), Three.js (3D), Vite |
| Gerçek zamanlı | Polling (`/durum` uçları) + `socket-server.js` (socket.io, yarış odası) |
| Bildirim | Web Push (minishlink/web-push, VAPID), PWA (service-worker, manifest) |
| Rapor | barryvdh/dompdf, CSV/Excel şablonları, WhatsApp/SMS veli bildirimi |
| Altyapı | Docker (php-fpm+nginx+mysql+redis), GitHub Actions CI, scheduler (dakikalık yoklama hatırlatma, günlük log temizliği) |

## 3. Roller
`admin` · `teacher` · `student` (+ veli: imzalı URL ile salt-okunur rapor). Rol = `roles.slug`, `RoleMiddleware`. Admin her şeyi, öğretmen kendi sınıf/ders/atamalarını yönetir. Öğrenci giriş: kullanıcı adı/şifre, **QR ile giriş**, oyun girişi (`/login/game`).

## 4. Modül haritası

### A. Kimlik & Kullanıcı
- Login/logout, throttle, zorla çıkış (`users.force_logout_at`, `CheckForcedLogout`), bakım modu (`CheckMaintenanceMode`)
- QR giriş: misafir QR üret → öğretmen okut/doğrula → öğrenci oturumu (`/qr/*`, `/qr-login/{token}`, `/qr-giris`, `/qr-okut`)
- Kullanıcı yönetimi (admin): CRUD, şifre sıfırlama, toplu öğrenci/öğretmen yükleme (Excel şablon), toplu silme, öğretmene sınıf atama (kademe veya sınıf bazlı)
- Profil: bilgi, logo/marka, slayt süresi ayarı, dashboard widget düzeni (`users.dashboard_layout`)

### B. Okul çekirdeği
`teachers`, `school_classes` (ad, şube, kademe, öğretim yılı), `students` (no, sınıf, veli, avatar), `courses` (ders), `announcements`, `attendances`, `grades`. CRUD + toplu işlemler (seçili sil / tümünü sil), REST `api/v1/{students,classes,courses}`.

### C. Ders (içerik) sistemi — en büyük modül (CourseController 1905 satır)
- Ders = slayt tabanlı içerik (`courses.lesson_payload` JSON), kapak görseli, medya yükleme, hiyerarşi (`parent_course_id`), sıralama, aktif/pasif
- Import/Export (`.coursepkg` paket), sınıf/öğretmene toplu atama, favoriler, önizleme
- Öğrenci: ders izleme, slayt ilerlemesi/devam noktası (`student_course_resumes`), tamamlama → XP
- `SlidePresentationService`, `CourseImageExtractor`

### D. Ödev & Atama
- Ders ödevi (`course_homeworks`: tür lesson/oyun, seviye aralığı, seviye puanları, ek dosya) + öğrenci ilerlemesi
- Oyun/etkinlik ödevi (`game_assignments` + `_school_class` + `_levels`) + `student_game_assignment_progresses`
- Öğretmene oyun atama (`teacher_game_assignments`) → `StudentGameAccessService` öğrencinin oynayabileceği oyunları belirler
- Soft delete

### E. Oyunlar / Etkinlikler (16 slug, `ActivityController::games()`)
live-quiz, block-grid-runner, block-3d-runner, compute-it-runner, lightbot-runner, line-trace-runner, silent-teacher-runner (Python), flamestone-game (bulmaca; `levels`,`scores`), keyboard-race, block-builder-studio (3D grid tasarım; `block_builder_designs`), connect-the-dots-runner (`dot_connect_levels`), python-editor, bee-garden-runner (CSS), dot-cards-runner, flowchart-runner, web-lab-runner.
Çoğu istemci tarafı oyun; sunucu `runner-open/{slug}` / `runner-grant/{slug}` ile erişim + XP verir.

### F. Gerçek zamanlı yarışmalar
1. **Canlı Quiz** (Kahoot tarzı): `live_quizzes → questions → sessions(lobby/live/finished, join_code) → participants → answers`; öğretmen başlat/sonraki/kilit/bitir/rapor, öğrenci katıl/cevapla/durum
2. **Canlı Yarışma** (oyun yarışı): `competition_rooms/participants`, ilerleme yüzdesi, seyirci
3. **Klavye Yarışı**: `rooms`, `race_results` (WPM, doğruluk), `api/race/*`, socket.io
4. **Günlük Kodlama Etkinliği**: `learning_tracks/lessons/coding_activities/activity_questions/question_options/activity_attempts/attempt_answers/leaderboards`, `daily_activity_assignments`, seri (`user_streaks`)

### G. Oyunlaştırma
XP (`user_profiles.xp`, `student_reports.total_xp`, `user_xp_logs`, `student_xp_grants`), avatar mağazası (XP ile satın alma, kademe/premium avatarlar), rozetler (`xp_threshold`), arkadaşlar, sınıf panosu (hazır mesajlar), sıralama, öğretmenden XP hediyesi. `StudentXpService` tek doğruluk kaynağı.

### H. Takip & Analitik
Canlı takip (aktif öğrenci/sınıf), `student_time_stats` + süre ping'i, `student_activity_logs` (30 gün+ temizlenir), başarı istatistikleri (`StudentSuccessAnalytics`, CSV/PDF), gelişim karnesi, sertifika, toplu rapor üretimi (adım adım task), giriş kartları (QR/şifre), toplu şifre sıfırlama.

### I. İletişim
Duyuru, bildirim merkezi (`notification_logs/preferences/reads`, `push_subscriptions`, `push_device_statuses`), öğrenci→öğretmen mesaj (`student_messages`), destek talepleri (`support_requests` + replies, misafir demo talebi), veli WhatsApp/SMS gelişim raporu.

### J. Sistem
Bakım modu, öğrenci verisi sıfırlama, `client/*` API (Firebase-benzeri doküman deposu: `document_records`, `user_profiles`, `game_states`; harici istemci uygulamaları için), PWA/offline, yasal sayfalar (gizlilik, çerez, şartlar, KVKK), SecureHeaders, opcache-clear deploy ucu.

## 5. Veritabanı (özet — 100+ tablo)
Detay: v1 migrationlarından çıkarıldı. Çekirdek ilişkiler:
```
roles 1─n users 1─1 teachers 1─n school_classes 1─n students 1─1 users
courses n─1 teachers/school_classes ; courses 1─n course_homeworks 1─n student_homework_progresses
game_assignments n─n school_classes ; game_assignments 1─n game_assignment_levels
students n─n avatars (student_avatar) ; students n─n badges (student_badge)
live_quizzes 1─n questions ; 1─n sessions 1─n participants/answers
competition_rooms 1─n competition_participants ; rooms 1─n race_results
coding_activities 1─n activity_questions 1─n question_options ; activity_attempts 1─n attempt_answers
```
Teknik borçlar (v2'de düzeltilecek): `student_credentials.plain_password` (düz metin şifre), `user_profiles`/`student_reports`/`content_progresses` ile `students` arasında çift kayıt (XP birden çok yerde), `rooms` vs `competition_rooms` vs `coding_live_quiz_rooms` üç ayrı oda modeli, tutarsız `user_id` / `student_id` anahtarları, rotalarda tekrar eden toplu-sil uçları (GET ile silme dahil), Türkçe/İngilizce karışık rota isimleri.

## 6. Rota özeti (331 → gruplar)
Public: `/`, `/login`, `/qr/*`, yasal sayfalar, `/demo-talep`, `/veli/gelisim-raporu/{student}` (signed), `/kapak-gorseli/*`, `/webpush/public-key`.
Ortak (auth): `/dashboard`, `/profilim`, `/etkinlikler`, `/dersler/{id}/onizle`, `/runner-*`, oyun sayfaları, `/app-notifications/mine`.
Yönetici+Öğretmen: `/students`, `/classes`, `/courses`, `/odevler`, `/canli-quiz`, `/canli-yarismalar`, `/canli-takip`, `/ogrenci-verileri/*`, `/veli-bildirim/*`, `/taleplerim`, `/kodlama-etkinlikleri/*`, `/qr-giris`, `/bildirimler`.
Sadece admin: `/kullanici-yonetimi/*`, `/sistem/*`, `/noktalari-birlestir/yonetim`.
Öğrenci: `/ogrenci/{panelim,derslerim,odevlerim,gelisim-karnem,rozetlerim,avatarlarim,arkadaslarim,sinif-panosu,canli-quiz,canli-yarismalar,gunluk-calisma,ogretmene-mesaj}`.
API: `api/v1/*` (admin/teacher), `api/client/*` (token), `api/race/*`, `api/flamestone/*`.
Tam liste: `docs/ROTALAR_V1.txt`.

# v2-okul planı

**Hedef yığın:** Laravel 12 + Blade + Tailwind/Alpine (Vue/Three yalnızca oyun modüllerinde), XAMPP uyumlu (MySQL/MariaDB, PHP 8.2).
**Prensipler:** tek XP defteri (ledger), tek oda/oturum modeli, düz metin şifre yok, yalnızca POST/DELETE ile silme, İngilizce kod + Türkçe arayüz (lang dosyaları), Service katmanı + Policy.

| Faz | Kapsam | Durum |
|---|---|---|
| 1 | Roller, auth, kullanıcı/öğretmen/sınıf/öğrenci CRUD, dashboard, seed | ✅ tamam |
| 2 | Dersler (slayt içerik, import/export), ödevler, atamalar | ✅ tamam |
| 3 | XP ledger, avatar/rozet, öğrenci portalı | ✅ tamam |
| 4 | Oyun kataloğu + erişim kontrolü, runner altyapısı (2 yerel oyun + paket yükleme) | ✅ tamam |
| 5 | Canlı quiz / yarışma / klavye yarışı (tek oturum modeli: `live_sessions`) | ✅ tamam |
| 6 | Takip, analitik, raporlar, veli bildirimi, push/PWA | ✅ tamam |
| 7 | Destek, mesajlaşma, duyuru, bakım, yasal sayfalar | ✅ tamam |
