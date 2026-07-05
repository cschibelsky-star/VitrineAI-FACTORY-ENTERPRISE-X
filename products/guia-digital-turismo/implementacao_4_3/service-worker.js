const APP_VERSION = '4.2.0';
const STATIC_CACHE = 'visite-sumare-static-v4-2';
const PAGE_CACHE = 'visite-sumare-pages-v4-2';
const STATIC_ASSETS = [
  './',
  'app.php',
  'atrativos.php',
  'eventos.php',
  'guia-comercial.php',
  'mapa.php',
  'favoritos.php',
  'perfil.php',
  'assets/css/style.css?v=4.2.0',
  'assets/js/app.js?v=4.2.0',
  'assets/img/hero-real.jpg',
  'assets/icons/icon-192.png',
  'assets/icons/icon-512.png',
  'manifest.json'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => cache.addAll(STATIC_ASSETS))
      .catch(() => null)
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys
        .filter(key => ![STATIC_CACHE, PAGE_CACHE].includes(key))
        .map(key => caches.delete(key))
    )).then(() => self.clients.claim())
  );
});

self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

self.addEventListener('fetch', event => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== location.origin) return;
  if (url.pathname.includes('/admin/')) return;

  const isStatic = /\.(css|js|png|jpg|jpeg|gif|svg|webp|ico|json)$/i.test(url.pathname);

  if (isStatic) {
    event.respondWith(cacheFirst(request));
    return;
  }

  if (request.mode === 'navigate' || /\.php$/i.test(url.pathname) || url.pathname === '/' || url.pathname === '') {
    event.respondWith(networkFirst(request));
  }
});

async function cacheFirst(request) {
  const cache = await caches.open(STATIC_CACHE);
  const cached = await cache.match(request);
  if (cached) return cached;

  const response = await fetch(request, { cache: 'no-store' });
  if (response && response.ok) cache.put(request, response.clone());
  return response;
}

async function networkFirst(request) {
  const cache = await caches.open(PAGE_CACHE);
  try {
    const response = await fetch(request, { cache: 'no-store' });
    if (response && response.ok) cache.put(request, response.clone());
    return response;
  } catch (e) {
    const cached = await cache.match(request);
    return cached || caches.match('app.php') || caches.match('./');
  }
}
