// CJ Hub service worker.
// Pages are always fetched from the network (they are per-user and carry CSRF tokens);
// only static assets are cached, plus an offline fallback for failed navigations.
const VERSION = 'v1';
const STATIC_CACHE = `cjhub-static-${VERSION}`;
const PRECACHE = ['/offline.html', '/icons/icon-192.png', '/icons/icon-512.png', '/favicon.svg'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(STATIC_CACHE).then((c) => c.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k.startsWith('cjhub-') && k !== STATIC_CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim()),
    );
});

const isStatic = (url) =>
    url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/') ||
    /\.(?:woff2?|ttf|css|js|svg|png|jpe?g|webp|ico)$/.test(url.pathname) && !url.pathname.startsWith('/media/');

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);

    // Navigations: network first, offline page when the network is down.
    if (req.mode === 'navigate') {
        event.respondWith(fetch(req).catch(() => caches.match('/offline.html')));
        return;
    }

    // Static assets (same-origin and the font CDN): stale-while-revalidate.
    const sameOrigin = url.origin === self.location.origin;
    const fonts = url.hostname === 'fonts.bunny.net';
    if ((sameOrigin && isStatic(url)) || fonts) {
        event.respondWith(
            caches.open(STATIC_CACHE).then(async (cache) => {
                const cached = await cache.match(req);
                const fresh = fetch(req).then((res) => {
                    if (res.ok || res.type === 'opaque') cache.put(req, res.clone());
                    return res;
                }).catch(() => cached);
                return cached || fresh;
            }),
        );
    }
});
