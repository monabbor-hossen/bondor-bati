/**
 * Bondor Bati Service Worker v3
 * - Cache-first for true static assets only (fonts, icons, offline page)
 * - Network-only for all PHP/authenticated pages (no stale auth data)
 */

const CACHE_NAME  = 'bondor-bati-v3';
const OFFLINE_URL = '/bondor-bati/offline.html';

// Only pre-cache the offline fallback — nothing authenticated
const PRECACHE_URLS = [OFFLINE_URL];

// Install: pre-cache offline page only
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

// Activate: purge old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(
                keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
            ))
            .then(() => self.clients.claim())
    );
});

// Fetch strategy
self.addEventListener('fetch', event => {
    const req = event.request;
    const url = new URL(req.url);

    // Only handle GET from our own origin
    if (req.method !== 'GET' || url.origin !== self.location.origin) return;

    // ── Dynamic/authenticated pages → Network-only (never cache) ──
    // These are PHP pages that contain user-specific data
    if (url.pathname.endsWith('index.php') || url.search.includes('url=') || url.pathname === '/bondor-bati/' || url.pathname === '/bondor-bati') {
        event.respondWith(
            fetch(req).catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }

    // ── True static assets → Cache-first ──────────────────────────
    // (fonts, icons, images loaded via direct path — not PHP)
    if (url.pathname.match(/\.(woff2?|ttf|png|jpg|jpeg|gif|svg|ico|webp|css|js)$/)) {
        event.respondWith(
            caches.match(req).then(cached => {
                if (cached) return cached;
                return fetch(req).then(response => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(req, clone));
                    }
                    return response;
                }).catch(() => caches.match(OFFLINE_URL));
            })
        );
        return;
    }

    // Everything else → network with offline fallback
    event.respondWith(
        fetch(req).catch(() => caches.match(OFFLINE_URL))
    );
});

// Background sync relay to client
self.addEventListener('sync', event => {
    if (event.tag === 'bondor-sync') {
        event.waitUntil(
            self.clients.matchAll().then(clients =>
                clients.forEach(c => c.postMessage({ type: 'SYNC_START' }))
            )
        );
    }
});
