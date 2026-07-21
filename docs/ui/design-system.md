# WMVAA Akademia — Design System & Asset Independence

## Overview
The design system of WMVAA Akademia has been completely decoupled and localized under `public/assets/`. It incorporates modern layout foundation patterns, dark/light theme switching, responsive sidebar navigation, and Lucide SVG icons.

## Asset Architecture & Independence

All assets reside strictly within `public/assets/` under the application document root:
- `public/assets/css/foundation.css` — Core tokens, color palettes, and global layout resets.
- `public/assets/css/dashboard.css` — Desktop sidebar, navbar, and card container styling.
- `public/assets/css/admin-dashboard.css` — Specialized admin dashboard widgets.
- `public/assets/css/dashboard-mobile.css` — Mobile breakpoint optimizations (<768px).
- `public/assets/css/mobile-first-polish.css` — Touch target sizing and spacing polish.
- `public/assets/js/theme-sync.js` — Unified light/dark theme persistence (`SpTheme`).
- `public/assets/js/lucide.min.js` — Local fallback library for Lucide SVG icons.

## Independence & Decoupling Audit
1. **Zero Runtime External Dependencies**: No runtime links pointing to external project directories (e.g. SPMB).
2. **Path Sanitization**: All view templates load assets using `base_url('assets/...')`.
3. **Offline Icon Fallback**: The master admin layout (`layouts/admin.php`) attempts CDN load for Lucide icons and falls back immediately to `assets/js/lucide.min.js` if offline/blocked:
   ```javascript
   if (typeof lucide === 'undefined') {
       document.write('<script src="' + baseUrl + 'assets/js/lucide.min.js?v=1.0.1"><\/script>');
   }
   ```
4. **Theme Persistence**: Theme preference (light/dark) is persisted in `localStorage` under key `sp_theme` and synchronized before initial paint to prevent FOUC (Flash of Unstyled Content).
