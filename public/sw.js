'use strict';

const VERSION = 'akademia-shell-v1.2.0';
const STATIC_CACHE = VERSION + '-static';
const RUNTIME_CACHE = VERSION + '-runtime';
const APP_BASE = new URL('./', self.registration.scope);
const appUrl = (path) => new URL(path, APP_BASE).toString();

const APP_SHELL = [
    'offline.html',
    'manifest.webmanifest',
    'assets/img/brand-mark.svg',
    'assets/img/pwa-icon-192.png',
    'assets/img/pwa-icon-maskable-192.png',
    'assets/img/pwa-icon-512.png',
    'assets/img/pwa-icon-maskable-512.png',
    'assets/css/auth.css',
    'assets/css/native-mobile.css',
    'assets/js/auth-ui.js',
    'assets/js/pwa.js',
    'assets/js/native-mobile.js',
    'assets/js/lucide.min.js'
].map(appUrl);

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(APP_SHELL))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys
                .filter((key) => key.startsWith('akademia-') && ![STATIC_CACHE, RUNTIME_CACHE].includes(key))
                .map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    const sameOrigin = url.origin === self.location.origin;

    if (request.mode === 'navigate') {
        // Authenticated HTML contains private school data. It is deliberately
        // never persisted in Cache Storage; offline mode receives a safe shell.
        event.respondWith(
            fetch(request).catch(() => caches.match(appUrl('offline.html')))
        );
        return;
    }

    if (!sameOrigin || (!url.pathname.includes('/assets/') && !url.pathname.endsWith('/manifest.webmanifest'))) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cached) => {
            const refresh = fetch(request).then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(RUNTIME_CACHE).then((cache) => cache.put(request, copy));
                }
                return response;
            }).catch(() => cached);
            return cached || refresh;
        })
    );
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') self.skipWaiting();
    if (event.data?.type === 'CLEAR_RUNTIME_CACHE') {
        event.waitUntil(caches.delete(RUNTIME_CACHE));
    }
});
