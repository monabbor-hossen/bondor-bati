/**
 * Bondor Bati POS — Service Worker
 * 
 * Strategy: Cache-First with Network Fallback
 * - On install, pre-caches the app shell (CSS, fonts, key pages).
 * - On fetch, serves from cache first; falls back to network.
 * - On activate, purges old cache versions.
 */

const CACHE_NAME = 'bondor-bati-v1';
const BASE_PATH  = '/bondor-bati';

// App shell: files to pre-cache on install
const APP_SHELL = [
    `${BASE_PATH}/`,
    `${BASE_PATH}/login`,
    `${BASE_PATH}/staff/closing`,
    `${BASE_PATH}/staff/dashboard`,
    `${BASE_PATH}/public/manifest.json`,
    `${BASE_PATH}/public/icons/icon-192x192.png`,
    `${BASE_PATH}/public/icons/icon-512x512.png`,
    // External CDN assets (Tailwind + Inter font)
    'https://cdn.tailwindcss.com',
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap'
];

// ─── INSTALL: Pre-cache the app shell ────────────────────────────────
self.addEventListener('install', (event) => {
    console.log('[SW] Installing service worker...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Pre-caching app shell');
                return cache.addAll(APP_SHELL);
            })
            .then(() => self.skipWaiting()) // Activate immediately
            .catch((err) => {
                console.warn('[SW] Pre-cache failed for some resources:', err);
            })
    );
});

// ─── ACTIVATE: Clean up old caches ───────────────────────────────────
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating service worker...');
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => name !== CACHE_NAME)
                        .map((name) => {
                            console.log('[SW] Deleting old cache:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => self.clients.claim()) // Take control of all pages
    );
});

// ─── FETCH: Cache-first, network fallback ────────────────────────────
self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Skip non-GET requests (POST to API, etc.)
    if (request.method !== 'GET') {
        return;
    }

    // Skip API calls — always go to network
    if (request.url.includes('/api/')) {
        return;
    }

    event.respondWith(
        caches.match(request)
            .then((cachedResponse) => {
                if (cachedResponse) {
                    // Serve from cache, but also update cache in background
                    event.waitUntil(
                        fetch(request)
                            .then((networkResponse) => {
                                if (networkResponse && networkResponse.status === 200) {
                                    const responseClone = networkResponse.clone();
                                    caches.open(CACHE_NAME)
                                        .then((cache) => cache.put(request, responseClone));
                                }
                            })
                            .catch(() => { /* Network unavailable, stale cache is fine */ })
                    );
                    return cachedResponse;
                }

                // Not in cache — fetch from network
                return fetch(request)
                    .then((networkResponse) => {
                        // Cache successful responses for future use
                        if (networkResponse && networkResponse.status === 200) {
                            const responseClone = networkResponse.clone();
                            caches.open(CACHE_NAME)
                                .then((cache) => cache.put(request, responseClone));
                        }
                        return networkResponse;
                    })
                    .catch(() => {
                        // Offline and not cached — show offline fallback
                        if (request.headers.get('accept').includes('text/html')) {
                            return new Response(
                                `<!DOCTYPE html>
                                <html lang="en">
                                <head>
                                    <meta charset="UTF-8">
                                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                    <title>Offline | Bondor Bati</title>
                                    <style>
                                        body { font-family: Inter, system-ui, sans-serif; background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 1rem; }
                                        .card { text-align: center; max-width: 20rem; }
                                        .icon { font-size: 3rem; margin-bottom: 1rem; }
                                        h1 { color: #1e293b; font-size: 1.25rem; font-weight: 800; }
                                        p { color: #64748b; font-size: 0.875rem; line-height: 1.5; }
                                        button { margin-top: 1.5rem; padding: 0.75rem 1.5rem; background: #f97316; color: white; border: none; border-radius: 0.75rem; font-weight: 700; font-size: 0.875rem; cursor: pointer; }
                                    </style>
                                </head>
                                <body>
                                    <div class="card">
                                        <div class="icon">📡</div>
                                        <h1>You're Offline</h1>
                                        <p>No internet connection detected. Please check your network and try again.</p>
                                        <button onclick="location.reload()">Retry</button>
                                    </div>
                                </body>
                                </html>`,
                                { headers: { 'Content-Type': 'text/html' } }
                            );
                        }
                    });
            })
    );
});
