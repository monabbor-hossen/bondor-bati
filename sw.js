/**
 * Bondor Bati Service Worker
 * Handles offline caching and background sync
 */

const CACHE_NAME = 'bondor-bati-v2';
const OFFLINE_URL = '/bondor-bati/offline.html';

// Assets to pre-cache
const PRECACHE_URLS = [
    '/bondor-bati/',
    '/bondor-bati/?url=dashboard',
    OFFLINE_URL,
];

// Install: pre-cache critical assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

// Activate: clean old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys
                .filter(key => key !== CACHE_NAME)
                .map(key => caches.delete(key))
            )
        ).then(() => self.clients.claim())
    );
});

// Fetch: network-first for API, cache-first for assets
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Skip non-GET requests
    if (event.request.method !== 'GET') return;

    // Skip external resources
    if (!url.origin.includes(self.location.origin)) return;

    // API requests: network-first
    if (url.search.includes('url=')) {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    // Cache successful responses
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    }
                    return response;
                })
                .catch(() => {
                    // Try cache, then offline page
                    return caches.match(event.request)
                        .then(cached => cached || caches.match(OFFLINE_URL));
                })
        );
        return;
    }

    // Static assets: cache-first
    event.respondWith(
        caches.match(event.request)
            .then(cached => cached || fetch(event.request)
                .then(response => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    }
                    return response;
                })
            )
            .catch(() => caches.match(OFFLINE_URL))
    );
});

// Background Sync: push offline data when connectivity resumes
self.addEventListener('sync', event => {
    if (event.tag === 'bondor-sync') {
        event.waitUntil(syncOfflineData());
    }
});

async function syncOfflineData() {
    // This will be called by the client-side IndexedDB sync logic
    const clients = await self.clients.matchAll();
    clients.forEach(client => {
        client.postMessage({ type: 'SYNC_START' });
    });
}
