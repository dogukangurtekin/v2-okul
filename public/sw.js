// v2-okul service worker: sayfalar network-first (yetkili icerik onbellege yazilmaz), yalniz ikon/offline sayfasi onbellekte.
const CACHE = 'v2-okul-static-1';
const STATIC = ['/offline.html', '/icon.svg'];

self.addEventListener('install', e => { e.waitUntil(caches.open(CACHE).then(c => c.addAll(STATIC))); self.skipWaiting(); });
self.addEventListener('activate', e => { e.waitUntil(caches.keys().then(ks => Promise.all(ks.filter(k => k !== CACHE).map(k => caches.delete(k)))).then(() => self.clients.claim())); });

self.addEventListener('fetch', e => {
  if (e.request.mode === 'navigate') {
    e.respondWith(fetch(e.request).catch(() => caches.match('/offline.html')));
  }
});

self.addEventListener('push', e => {
  let d = {}; try { d = e.data ? e.data.json() : {}; } catch (_) {}
  e.waitUntil(self.registration.showNotification(d.title || 'v2-okul', { body: d.body || '', icon: '/icon.svg', data: { url: d.url || '/bildirimlerim' } }));
});

self.addEventListener('notificationclick', e => {
  e.notification.close();
  e.waitUntil(clients.openWindow(e.notification.data.url));
});
