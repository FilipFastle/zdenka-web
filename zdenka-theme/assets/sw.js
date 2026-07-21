/* ─────────────────────────────────────────
   Service Worker – Zdenka Cibulová
   Cache version managed via Zdenka Setup
───────────────────────────────────────── */

// Version injected by PHP (see functions.php)
var CACHE_VER = self.CACHE_VERSION || 'v1';
var CACHE_NAME = 'zdenka-' + CACHE_VER;

// Assets to pre-cache on install
var PRECACHE = [
    '/',
    '/ponuky/',
    '/kontakt/',
    '/odhad/',
];

// Cache-first patterns (static assets)
var CACHE_FIRST = [
    /\.css(\?|$)/,
    /\.js(\?|$)/,
    /\.woff2?(\?|$)/,
    /fonts\.googleapis\.com/,
    /fonts\.gstatic\.com/,
    /\/assets\/images\//,
];

// Network-first patterns (pages, API)
var NETWORK_FIRST = [
    /\/wp-admin/,
    /\/wp-json/,
    /admin-ajax\.php/,
];

// ── INSTALL ──
self.addEventListener('install', function (e) {
    self.skipWaiting();
    e.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            return cache.addAll(PRECACHE).catch(function () {});
        })
    );
});

// ── ACTIVATE – delete old caches ──
self.addEventListener('activate', function (e) {
    e.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.filter(function (k) { return k !== CACHE_NAME; })
                    .map(function (k) {
                        console.log('[SW] Deleting old cache:', k);
                        return caches.delete(k);
                    })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

// ── FETCH ──
self.addEventListener('fetch', function (e) {
    var url = e.request.url;

    // Skip non-GET, admin, ajax
    if (e.request.method !== 'GET') return;
    for (var i = 0; i < NETWORK_FIRST.length; i++) {
        if (NETWORK_FIRST[i].test(url)) return;
    }

    // Cache-first for static assets
    var isCacheFirst = CACHE_FIRST.some(function (r) { return r.test(url); });
    if (isCacheFirst) {
        e.respondWith(
            caches.match(e.request).then(function (cached) {
                if (cached) return cached;
                return fetch(e.request).then(function (res) {
                    var clone = res.clone();
                    caches.open(CACHE_NAME).then(function (c) { c.put(e.request, clone); });
                    return res;
                });
            })
        );
        return;
    }

    // Network-first for HTML pages
    e.respondWith(
        fetch(e.request).then(function (res) {
            if (res.ok) {
                var clone = res.clone();
                caches.open(CACHE_NAME).then(function (c) { c.put(e.request, clone); });
            }
            return res;
        }).catch(function () {
            return caches.match(e.request).then(function (cached) {
                return cached || caches.match('/');
            });
        })
    );
});
