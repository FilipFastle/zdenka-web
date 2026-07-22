/* ─────────────────────────────────────────
   Service Worker – Zdenka Cibulová
   Cache version managed via Zdenka Setup
───────────────────────────────────────── */

// Version injected by PHP (see functions.php)
var CACHE_VER = self.CACHE_VERSION || 'v1';
// suffix „s2" vynúti prečistenie starej cache (ktorá mohla obsahovať HTML s tokenmi)
var CACHE_NAME = 'zdenka-' + CACHE_VER + '-s2';

// Nič HTML sa neprecachuje (kvôli časovým tokenom vo formulároch)
var PRECACHE = [];

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
// DÔLEŽITÉ: HTML stránky NIKDY neukladáme do cache ani neservírujeme z cache.
// Obsahujú časovo obmedzené bezpečnostné tokeny (nonce, anti-spam) – zastaraná
// verzia zo cache by rozbila odosielanie formulárov. Cachujeme len statické súbory.
self.addEventListener('fetch', function (e) {
    var url = e.request.url;
    var req = e.request;

    // Len GET; POST a pod. idú vždy priamo na sieť
    if (req.method !== 'GET') return;

    // Navigácie (HTML stránky) → vždy zo siete, bez cache
    var accept = req.headers.get('accept') || '';
    if (req.mode === 'navigate' || accept.indexOf('text/html') !== -1) return;

    // Admin / AJAX / REST → priamo na sieť
    for (var i = 0; i < NETWORK_FIRST.length; i++) {
        if (NETWORK_FIRST[i].test(url)) return;
    }

    // Cache-first len pre statické assety (css/js/fonty/obrázky)
    var isCacheFirst = CACHE_FIRST.some(function (r) { return r.test(url); });
    if (!isCacheFirst) return; // ostatné nechaj na prehliadač (bez SW zásahu)

    e.respondWith(
        caches.match(req).then(function (cached) {
            if (cached) return cached;
            return fetch(req).then(function (res) {
                if (res && res.ok) {
                    var clone = res.clone();
                    caches.open(CACHE_NAME).then(function (c) { c.put(req, clone); });
                }
                return res;
            });
        })
    );
});
