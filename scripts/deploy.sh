#!/usr/bin/env bash
# v2-okul güncelleme betiği: sunucuda proje klasöründe çalıştırın →  bash scripts/deploy.sh
# İlk kurulum için önce .env'i hazırlayın (.env.production.example). Hata olursa durur ve siteyi bakımda BIRAKMAZ.
set -euo pipefail
cd "$(dirname "$0")/.."

PHP="${PHP_BIN:-php}"
COMPOSER="${COMPOSER_BIN:-composer}"

[ -f .env ] || { echo "HATA: .env yok. .env.production.example dosyasını .env olarak kopyalayıp doldurun."; exit 1; }
grep -q '^APP_ENV=production' .env || echo "UYARI: APP_ENV=production değil."
grep -qE '^APP_DEBUG=(false|0)' .env || { echo "HATA: APP_DEBUG=false olmalı (hata sayfaları gizli bilgi sızdırır)."; exit 1; }

echo "→ Bağımlılıklar"
$COMPOSER install --no-dev --prefer-dist --optimize-autoloader --no-interaction

grep -qE '^APP_KEY=base64:' .env || { echo "→ APP_KEY üretiliyor"; $PHP artisan key:generate --force; }

echo "→ Bakım modu AÇ (yalnızca veritabanı değişirken)"
$PHP artisan down --retry=30 || true
trap '$PHP artisan up || true' EXIT

echo "→ Veritabanı"
$PHP artisan migrate --force

echo "→ Depolama bağlantısı ve izinler"
$PHP artisan storage:link 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache

echo "→ Önbellekler"
$PHP artisan optimize:clear
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache

echo "✓ Dağıtım tamam ($(git rev-parse --short HEAD 2>/dev/null || echo 'git yok'))"
