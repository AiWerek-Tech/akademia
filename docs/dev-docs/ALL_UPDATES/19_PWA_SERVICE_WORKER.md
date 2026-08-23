# WMVAA Akademia — PWA & Service Worker

## 1. Overview

Aplikasi mendukung **Progressive Web App (PWA)** sehingga dapat diinstal sebagai aplikasi native di perangkat mobile dan desktop.

---

## 2. Web App Manifest (`public/manifest.webmanifest`)

### 2.1 Configuration

```json
{
    "id": "./",
    "name": "IALOS Education",
    "short_name": "IALOS",
    "description": "Integrated Academic Learning Operating System untuk SMP dan SMA.",
    "lang": "id-ID",
    "dir": "ltr",
    "start_url": "./dashboard?source=pwa",
    "scope": "./",
    "display": "standalone",
    "display_override": ["window-controls-overlay", "standalone", "minimal-ui"],
    "orientation": "any",
    "background_color": "#f4f7fb",
    "theme_color": "#4f46e5",
    "categories": ["education", "productivity"]
}
```

### 2.2 Icons

| Size | Type | Purpose |
|---|---|---|
| 192x192 | PNG | Any (standard) |
| 192x192 | PNG | Maskable |
| 512x512 | PNG | Any (splash) |
| 512x512 | PNG | Maskable |

### 2.3 Shortcuts

| Shortcut | URL | Deskripsi |
|---|---|---|
| Dashboard | `./dashboard` | Buka dashboard |
| Jadwal Saya | `./portal/schedule` | Buka jadwal |

---

## 3. Service Worker (`public/sw.js`)

### 3.1 Version

```
VERSION = 'akademia-shell-v1.2.0'
```

### 3.2 Cache Strategy

| Cache Name | Strategy | Isi |
|---|---|---|
| `akademia-shell-v1.x.x-static` | Pre-cache | App shell files |
| `akademia-shell-v1.x.x-runtime` | Cache-then-network | Runtime assets |

### 3.3 App Shell Files (Pre-cached)

```javascript
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
];
```

### 3.4 Event Handlers

#### Install

```javascript
self.addEventListener('install', (event) => {
    // Pre-cache app shell files
    // Skip waiting for immediate activation
});
```

#### Activate

```javascript
self.addEventListener('activate', (event) => {
    // Clean old caches
    // Claim all clients
});
```

#### Fetch

```javascript
self.addEventListener('fetch', (event) => {
    // GET requests only
    // Navigation requests: Network-first, fallback to offline.html
    // Same-origin assets: Cache-then-network (stale-while-revalidate)
    // Cross-origin: Pass through (no caching)
});
```

#### Message

```javascript
self.addEventListener('message', (event) => {
    // SKIP_WAITING: Force update
    // CLEAR_RUNTIME_CACHE: Clear runtime cache
});
```

### 3.5 Security Considerations

- **HTML pages are NEVER cached**: Navigasi selalu mengambil dari network. Offline mode hanya menampilkan `offline.html` (safe shell tanpa data sekolah)
- **Assets cached**: Hanya file statis (CSS, JS, gambar) yang di-cache
- **Runtime cache**: Assets yang di-cache saat pertama diakses

---

## 4. Offline Page (`public/offline.html`)

Halaman fallback yang ditampilkan saat user offline. Berisi:
- Pesan "Anda sedang offline"
- Instruksi untuk memeriksa koneksi
- Link untuk retry

---

## 5. PWA Features

### 5.1 Install Prompt (`public/assets/js/pwa.js`)

```javascript
// Detect install prompt
window.addEventListener('beforeinstallprompt', (e) => {
    // Show custom install button
});

// Detect installed
window.addEventListener('appinstalled', () => {
    // Hide install button
});
```

### 5.2 Meta Tags

```html
<meta name="theme-color" content="#5b5ce2">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="IALOS">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="apple-touch-icon" href="/assets/img/pwa-icon-192.png">
```

### 5.3 Install Button

Button "Instal Aplikasi" ditampilkan di dropdown user profile jika tersedia:

```html
<li data-pwa-install hidden>
    <button data-pwa-install>Instal Aplikasi</button>
</li>
```

---

## 6. Cache Management

### 6.1 Version Updates

Ketika versi service worker berubah:
1. New service worker installed
2. Old caches cleaned during activate
3. `clients.claim()` untuk takeover segera

### 6.2 Manual Cache Clear

```javascript
// Via message
navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_RUNTIME_CACHE' });
```

---

## 7. Performance Optimization

### 7.1 Assets Loading

- CSS: Render-blocking (diletakkan di head)
- JS: Defer loading (sebelum closing body)
- Icons: Lazy loading via `lucide.createIcons()`
- Images: Lazy loading via browser native

### 7.2 Offline-First Strategy

| Resource Type | Strategy |
|---|---|
| **HTML Pages** | Network-first (no caching) |
| **Static Assets** | Cache-then-network |
| **API Calls** | Network-only (no caching) |
