/*
 * Tarayıcıya yakın (jsdom) testlerin orkestratörü: her testten önce test veritabanını SIFIRDAN kurar.
 *   npm run test:dom         → oyun akışları (öğrenci + personel) + yeniden yükleme/ilerleme
 *   npm run test:dom:full    → + Lightbot istemci/sunucu fuzz (≈2 dk)
 *   npm run test:dom -- --only=games2   → yalnızca adı eşleşen takım(lar)
 * Windows/XAMPP:  set PHP_BIN=C:\xampp\php\php.exe
 */
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { HERE, CACHE, PHP, testEnv } from './dom.mjs';

const full = process.argv.includes('--full');
const only = (process.argv.find(a => a.startsWith('--only=')) || '').slice(7);   // örn. --only=games2
const allSuites = [['games.mjs', 'student'], ['games.mjs', 'staff'], ['games2.mjs', 'student'], ['games2.mjs', 'staff'], ['pyworker.mjs'], ['live.mjs'], ['persist.mjs'], ...(full ? [['fuzz.mjs']] : [])];
const suites = only ? allSuites.filter(([f]) => f.includes(only)) : allSuites;
if (!suites.length) { console.error(`--only=${only} hiçbir takımla eşleşmedi`); process.exit(2); }
fs.mkdirSync(CACHE, { recursive: true });
const env = testEnv();

const ver = spawnSync(PHP, ['-r', 'echo PHP_VERSION;'], { env, encoding: 'utf8' });
if (ver.error || ver.status !== 0) {
  console.error(`PHP çalıştırılamadı (${PHP}). XAMPP için: set PHP_BIN=C:\\xampp\\php\\php.exe`);
  process.exit(2);
}
if (!fs.existsSync(path.join(HERE, '..', '..', 'vendor', 'autoload.php'))) {
  console.error('vendor/ yok: önce `composer install` çalıştırın.');
  process.exit(2);
}
console.log(`PHP ${ver.stdout} · test veritabanı: ${env.DB_DATABASE}\n`);

let failed = 0;
for (const [file, ...args] of suites) {
  const seed = spawnSync(PHP, [path.join(HERE, 'seed.php')], { env, encoding: 'utf8' });
  if (seed.status !== 0) { console.error('seed başarısız:\n' + seed.stdout + seed.stderr); process.exit(2); }
  console.log(`━━━ ${file} ${args.join(' ')} ━━━`);
  const r = spawnSync(process.execPath, [path.join(HERE, file), ...args], { env, stdio: 'inherit' });
  if (r.status !== 0) failed++;
  console.log('');
}
console.log(failed ? `✗ ${failed} takım başarısız` : '✓ tüm jsdom takımları geçti');
process.exit(failed ? 1 : 0);
