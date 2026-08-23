# WMVAA Akademia — UI/UX Design System & Frontend

## 1. Design System Overview

Aplikasi menggunakan custom design system yang dibangun di atas Bootstrap 5 dengan pendekatan **mobile-first** dan **role-based UI rendering**.

### 1.1 Design Principles

1. **Mobile-First**: Semua halaman dioptimasi untuk mobile sebelum desktop
2. **Role-Based UI**: Sidebar menu dan konten berbeda berdasarkan role pengguna
3. **Dark/Light Theme**: Mendukung tema gelap dan terang
4. **PWA-Ready**: Dapat diinstal sebagai aplikasi native
5. **Offline-Aware**: Service worker untuk caching statis
6. **Accessibility**: Skip navigation, ARIA labels, semantic HTML

---

## 2. Asset Architecture

Semua aset terlokalisasi di `public/assets/` dengan zero runtime external dependencies (kecuali CDN fallbacks).

### 2.1 CSS Files

| File | Fungsi | Versi |
|---|---|---|
| `foundation.css` | Core design tokens, color palette, global layout resets | 1.0.1 |
| `dashboard.css` | Desktop sidebar, navbar, card container styling | 1.0.1 |
| `admin-dashboard.css` | Admin dashboard widgets khusus | 1.0.1 |
| `dashboard-mobile.css` | Mobile breakpoint optimizations (<768px) | 1.0.1 |
| `mobile-first-polish.css` | Touch target sizing, spacing polish | 1.0.0 |
| `academia-ui.css` | Academic-specific UI components | 2.1.3 |
| `native-mobile.css` | Native mobile app-like styling | 1.2.0 |
| `auth.css` | Login/authentication page styling | - |
| `mobile-form-optimization.css` | Optimasi form di mobile | - |
| `mobile-form-validation.css` | Validasi form mobile | - |
| `mobile-progress-bar-prominent.css` | Progress bar prominent mobile | - |
| `mobile-public-polish.css` | Polish untuk halaman publik | - |
| `mobile-upload-form.css` | Upload form mobile | - |
| `public.css` | Halaman publik | - |
| `admin-academic-years.css` | Tahun akademik admin | - |

### 2.2 JavaScript Files

| File | Fungsi | Versi |
|---|---|---|
| `academia-ui.js` | Core UI interactions, sidebar, dropdowns | 2.0.1 |
| `native-mobile.js` | Native mobile app behaviors | 1.2.0 |
| `theme-sync.js` | Dark/light theme persistence (`SpTheme`) | 1.0.3 |
| `pwa.js` | PWA install prompt, service worker registration | 1.1.0 |
| `lucide.min.js` | Lucide icon library (local fallback) | - |
| `auth-ui.js` | Login page interactions | - |
| `command-palette.js` | Command palette (Cmd+K) | - |
| `help-data.js` | Help system data | - |
| `help-drawer.js` | Help drawer UI | - |
| `mobile-dashboard-optimizer.js` | Dashboard mobile optimizations | - |
| `mobile-document-upload.js` | Document upload mobile | - |
| `mobile-form-validator.js` | Form validation mobile | - |
| `mobile-progress-bar-prominent.js` | Progress bar mobile | - |
| `no-horizontal-scroll-detector.js` | Detect horizontal scroll issues | - |
| `performance-optimizer.js` | Performance optimization utilities | - |
| `share-modal.js` | Share functionality modal | - |
| `wizard-draft-manager.js` | Wizard/draft state management | - |

### 2.3 External CDN Resources

| Resource | CDN URL | Versi |
|---|---|---|
| Bootstrap CSS | `cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css` | 5.3.3 |
| Bootstrap JS Bundle | `cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js` | 5.3.3 |
| Bootstrap Icons | `cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css` | 1.11.3 |
| SweetAlert2 CSS | `cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css` | 11.x |
| SweetAlert2 JS | `cdn.jsdelivr.net/npm/sweetalert2@11` | 11.x |
| Lucide Icons | `unpkg.com/lucide@0.309.0/dist/umd/lucide.min.js` | 0.309.0 |
| Google Fonts | `fonts.googleapis.com/css2` | - |

### 2.4 Google Fonts

| Font | Weights | Usage |
|---|---|---|
| **Poppins** | 300-800 | Heading & brand |
| **Inter** | 300-700 | Body text (default) |
| **Plus Jakarta Sans** | 400-800 | Alternative body |
| **Nunito** | 300-700 | Alternative heading |
| **Roboto** | 300-700 | Alternative |
| **Open Sans** | 300-700 | Alternative |

---

## 3. Layout System

### 3.1 Master Layout (`app/Views/layouts/admin.php`)

Layout utama menggunakan struktur:

```html
<body>
  <div class="layout-wrapper" id="layoutWrapper">
    <!-- Sidebar Backdrop (mobile) -->
    <div class="sidebar-overlay"></div>
    
    <!-- Sidebar Navigation -->
    <aside class="sidebar" role="navigation">
      <div class="sidebar-brand">...</div>
      <div class="sidebar-search">...</div>
      <ul class="sidebar-menu" id="sidebarMenu">
        <!-- Menu items rendered based on role -->
      </ul>
    </aside>
    
    <!-- Main Container -->
    <div class="main-container">
      <!-- Navbar -->
      <header class="navbar">
        <button id="btnToggleSidebar">Menu</button>
        <nav>Breadcrumb</nav>
        <!-- Unit Selector -->
        <!-- Period Selector -->
        <!-- Dark Mode Toggle -->
        <!-- User Profile Dropdown -->
      </header>
      
      <!-- Content Body -->
      <main class="content-body" id="main-content">
        <?= $this->renderSection('main_content') ?>
      </main>
      
      <!-- Footer -->
      <footer class="footer">...</footer>
    </div>
  </div>
  
  <!-- Mobile Bottom Navigation -->
  <nav class="dashboard-mobile-bottom-nav">
    <!-- 4-5 bottom nav items based on role -->
  </nav>
</body>
```

### 3.2 Sidebar Structure

```
Sidebar
├── Brand Logo & Name
├── Search Input (with / shortcut)
└── Menu Groups (collapsible)
    ├── Menu Header
    ├── Menu Item (direct link)
    └── Menu Group (has-submenu)
        ├── Toggle Button (icon + label + chevron)
        └── Submenu List
            ├── Submenu Item (link)
            └── ...
```

### 3.3 Sidebar Features

| Feature | Deskripsi |
|---|---|
| **Collapse Toggle** | Button hamburger untuk collapse/expand sidebar |
| **Search** | Filter menu berdasarkan keyword |
| **Active State** | Menu aktif ditandai dengan class `active` dan `open` |
| **Collapsible Groups** | Submenu dapat di-expand/collapse |
| **Mobile Overlay** | Backdrop overlay saat sidebar terbuka di mobile |
| **LocalStorage Persistence** | State collapsed tersimpan di localStorage |
| **Keyboard Shortcut** | `/` untuk fokus ke search |

### 3.4 Responsive Breakpoints

| Breakpoint | Comportment |
|---|---|
| **≥ 992px (lg)** | Desktop: sidebar visible, full layout |
| **< 992px** | Mobile: sidebar hidden, bottom nav visible |

---

## 4. Theme System

### 4.1 Theme Mode

- **Light Mode** (default)
- **Dark Mode**

### 4.2 Theme Persistence

```javascript
// theme-sync.js
SpTheme.init({ serverTheme: 'purple', scope: 'dashboard' });
SpTheme.initDarkMode();  // Load from localStorage before paint
```

Theme disimpan di localStorage key `sp_theme` dan disinkronkan sebelum render pertama untuk mencegah FOUC.

### 4.3 Theme Toggle

```html
<button id="btnToggleTheme">
  <i data-lucide="moon" id="themeToggleIcon"></i>
</button>
```

### 4.4 CSS Theme Variables

```html
<html data-theme-color="#6366f1" data-service-worker="/sw.js">
```

Warna theme dikontrol oleh `system_settings` tabel (group: `appearance`, key: `primary_color`).

---

## 5. Mobile Design

### 5.1 Native Mobile App Feel

Aplikasi dirancang seperti native mobile app:

| Feature | Implementasi |
|---|---|
| **Bottom Navigation** | `dashboard-mobile-bottom-nav` dengan 4-5 item + "Lainnya" button |
| **Mobile Header** | Judul halaman + role di navbar mobile |
| **Touch Targets** | Minimum 44px touch targets |
| **Swipe Gestures** | Sidebar toggle via hamburger |
| **PWA Install** | Prompt instalasi melalui `pwa.js` |
| **Standalone Display** | `display: standalone` di manifest |
| **Safe Area** | `env(safe-area-inset-*)` untuk notch |

### 5.2 Bottom Navigation Items (Per Role)

#### Admin/Super Admin
1. Beranda (house)
2. Guru (users)
3. Tugas (briefcase)
4. Jadwal (calendar-days)
5. Nilai (clipboard-check)

#### Guru
1. Beranda (house)
2. Mengajar (sparkles)
3. Jadwal (calendar-days)
4. Pilihan (users-round)

#### Wali Kelas
1. Beranda (house)
2. Mengajar (sparkles)
3. Kelas (school)
4. Jadwal (calendar-days)
5. Pilihan (users-round)

#### Siswa
1. Beranda (house)
2. Pilihan (list-checks)
3. Kalender (calendar-range)

### 5.3 Mobile Optimizations

| CSS File | Fungsi |
|---|---|
| `dashboard-mobile.css` | Breakpoint optimizations |
| `mobile-first-polish.css` | Touch target sizing |
| `mobile-form-optimization.css` | Form layout mobile |
| `mobile-form-validation.css` | Validation messages mobile |
| `mobile-progress-bar-prominent.css` | Progress bar visibility |
| `native-mobile.css` | App-like chrome, safe areas |
| `mobile-upload-form.css` | Upload form mobile |

---

## 6. Icon System

### 6.1 Lucide Icons

Semua icon menggunakan **Lucide Icons**:

```html
<i data-lucide="icon-name"></i>
```

Icon di-render oleh JavaScript setelah DOM loaded:
```javascript
lucide.createIcons();
```

### 6.2 Icon Fallback

```javascript
// CDN load dengan local fallback
if (typeof lucide === 'undefined') {
    document.write('<script src="/assets/js/lucide.min.js"><\/script>');
}
```

### 6.3 Common Icons Used

| Icon | Usage |
|---|---|
| `house` / `layout-dashboard` | Dashboard |
| `users` / `users-round` | Guru/Users |
| `user-round` | Single user |
| `briefcase` | Tugas/Assignment |
| `calendar-days` | Jadwal/Schedule |
| `calendar-check` | Calendar verified |
| `calendar-range` | Calendar range |
| `clipboard-check` | Assessment |
| `bar-chart` / `bar-chart-3` | Workload/Analytics |
| `school` | Kelas/Classroom |
| `graduation-cap` | Siswa/Student |
| `book-open` | Mapel/Subject |
| `door-closed` | Ruang/Room |
| `shield-check` | Piket/Duty |
| `sparkles` | Teaching workspace |
| `trophy` | Ekstrakurikuler |
| `star` | Kokurikuler |
| `settings-2` / `cog` | Settings |
| `brain` | AI Copilot |
| `heart-handshake` | Intervensi |
| `network` | Lineage/Outcomes |
| `route` | ATP/Sequences |
| `target` | Objectives |
| `milestone` | CP/Outcomes |
| `package-open` | Learning packs |
| `gauge` | Quality dashboard |
| `notebook-pen` | Reflection |
| `scan-eye` | Supervision |
| `plug-zap` | Integration/Sync |
| `activity` | Diagnostics |
| `scroll-text` | Audit log |
| `palette` | Appearance |
| `building` | School profile |
| `server` | Database |

---

## 7. Component Library

### 7.1 KPI Cards

```html
<div class="card native-kpi-card border-0 shadow-sm rounded-4">
  <div class="card-body d-flex align-items-center gap-3">
    <span class="native-kpi-icon bg-primary bg-opacity-10 text-primary rounded-3 p-3">
      <i data-lucide="icon"></i>
    </span>
    <div>
      <div class="text-muted fs-8 text-uppercase fw-semibold">Label</div>
      <div class="fs-4 fw-bold">Value</div>
    </div>
  </div>
</div>
```

### 7.2 Dashboard Hero Card

```html
<div class="card border-0 shadow-sm rounded-4 dashboard-hero" 
     style="background:linear-gradient(135deg,#17324d,#2563eb)">
  <div class="card-body text-white">
    <span class="badge rounded-pill" style="background:rgba(255,255,255,.16)">Badge</span>
    <h2>Greeting, Name</h2>
    <p>Description</p>
  </div>
</div>
```

### 7.3 Context Panel

```html
<div class="dashboard-context-panel bg-white bg-opacity-10 rounded-4 p-3">
  <div class="small text-white text-opacity-75">Cakupan data</div>
  <div class="fw-bold">Unit Name</div>
  <div class="small text-white text-opacity-75 mt-2">Periode aktif</div>
  <div class="fw-bold">Period Name</div>
</div>
```

### 7.4 Activity Table

```html
<div class="card border-0 shadow-sm rounded-4">
  <div class="card-body p-4">
    <h5 class="fw-bold">Aktivitas Terbaru</h5>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>User</th><th>Modul</th><th>Aksi</th><th>Catatan</th><th>Waktu</th></tr></thead>
        <tbody>...</tbody>
      </table>
    </div>
  </div>
</div>
```

### 7.5 Readiness Progress

```html
<div class="progress mb-4" style="height:8px">
  <div class="progress-bar" style="width:75%"></div>
</div>
<div class="row g-2">
  <div class="col-md-6">
    <a class="d-flex align-items-center justify-content-between border rounded-3 p-3">
      <span><i data-lucide="check-circle-2" class="text-success"></i> Item Label</span>
      <i data-lucide="chevron-right"></i>
    </a>
  </div>
</div>
```

### 7.6 Quick Action Buttons

```html
<a href="/path" class="btn btn-outline-primary text-start rounded-3">
  <i data-lucide="icon" class="me-2" style="width:17px"></i>
  Label
</a>
```

---

## 8. Typography

### 8.1 Font Configuration

Font family dan size dikontrol dari database `system_settings`:

```php
$appInfo->font = $_app['font_family'] ?? 'Inter';
$appInfo->fontSize = $_app['font_size'] ?? '14';
```

```html
<style>
    body { font-family: '<?= esc($appInfo->font) ?>', sans-serif !important; 
           font-size: <?= (int)($appInfo->fontSize) ?>px !important; }
</style>
```

### 8.2 Typography Scale

| Class | Usage |
|---|---|
| `fs-4` | KPI values |
| `fs-3` | Executive metrics |
| `fw-bold` | Headings |
| `fw-semibold` | Labels |
| `fs-8` | Small labels (custom) |
| `text-muted` | Secondary text |
| `text-white text-opacity-75` | Hero card text |

---

## 9. Color System

### 9.1 Theme Color

Primary color dikontrol dari database: `system_settings.appearance.primary_color` (default: `#6366f1`).

### 9.2 Bootstrap Utility Colors

| Color | Usage |
|---|---|
| `primary` | Buttons, badges, links |
| `success` | Positive states |
| `info` | Informational |
| `warning` | Caution |
| `danger` | Errors, critical |

### 9.3 Gradient

- **Dashboard Hero**: `linear-gradient(135deg, #17324d, #2563eb)`

---

## 10. Command Palette

Aplikasi mendukung command palette (seperti VS Code):

- **Trigger**: Keyboard shortcut (biasanya Cmd+K atau Ctrl+K)
- **File**: `command-palette.js`
- **Fungsi**: Quick navigation ke halaman manapun

---

## 11. Help System

- **File**: `help-data.js` + `help-drawer.js`
- **Fungsi**: Drawer bantuan dengan konteks halaman
- **Trigger**: Help button di UI

---

## 12. Form Patterns

### 12.1 Context Selector (Auto-Submit)

```html
<select class="form-select context-select" data-auto-submit>
    <option value="1">Option 1</option>
</select>
```

### 12.2 CSRF Field

```html
<?= csrf_field() ?>
```

### 12.3 Breadcrumb

```html
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
        <li class="breadcrumb-item active">Page Title</li>
    </ol>
</nav>
```

---

## 13. Alert System

### 13.1 Session Flash Messages

```php
<?php if (session('success')): ?>
    <div class="alert alert-success rounded-3"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-danger rounded-3"><?= esc(session('error')) ?></div>
<?php endif; ?>
```

### 13.2 SweetAlert2

Digunakan untuk konfirmasi aksi, loading indicators, dan notifikasi.
