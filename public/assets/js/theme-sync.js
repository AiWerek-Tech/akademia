/**
 * SPMB WMVAA — Unified Theme Sync (color + dark mode)
 * Single source of truth: localStorage key "theme" + cookie "theme"
 */
(function (window) {
    'use strict';

    const VALID_THEMES = ['purple', 'navy', 'lightblue', 'emerald', 'red', 'orange', 'rose'];
    const DARK_MODE_KEY = 'theme';

    function readCssVar(name) {
        return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    }

    function getThemePrimary() {
        return readCssVar('--sp-primary');
    }

    function getThemeAccent() {
        return readCssVar('--sp-accent');
    }

    function getThemePrimaryRgb() {
        return readCssVar('--sp-primary-rgb');
    }

    function isDarkMode() {
        return localStorage.getItem(DARK_MODE_KEY) === 'dark';
    }

    function applyDarkModeClasses(isDark) {
        const root = document.documentElement;
        root.classList.toggle('dark-mode', isDark);
        root.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');
        root.style.colorScheme = isDark ? 'dark' : 'light';

        if (document.body) {
            document.body.classList.toggle('dark-mode', isDark);
        }
    }

    function persistDarkMode(isDark) {
        localStorage.setItem(DARK_MODE_KEY, isDark ? 'dark' : 'light');
        document.cookie = 'theme=' + (isDark ? 'dark' : 'light') + '; path=/; max-age=31536000; SameSite=Lax';
    }

    function syncMetaThemeColor() {
        const meta = document.querySelector('meta[name="theme-color"]');
        if (!meta) {
            return;
        }

        if (isDarkMode()) {
            meta.setAttribute('content', readCssVar('--sp-body-bg') || '#090d16');
        } else {
            const primary = getThemePrimary();
            if (primary) {
                meta.setAttribute('content', primary);
            }
        }
    }

    function initDarkMode() {
        applyDarkModeClasses(isDarkMode());
    }

    function setDarkMode(isDark, options) {
        const opts = options || {};
        applyDarkModeClasses(isDark);

        if (opts.persist !== false) {
            persistDarkMode(isDark);
        }

        syncMetaThemeColor();

        document.dispatchEvent(new CustomEvent('dark-mode-change', {
            detail: { isDark: isDark },
        }));
    }

    function toggleDarkMode() {
        const next = !isDarkMode();
        setDarkMode(next);
        return next;
    }

    function updateToggleIcon(iconEl, isDark) {
        const iconId = iconEl ? iconEl.id : 'themeToggleIcon';
        const liveIcon = iconEl && iconEl.isConnected
            ? iconEl
            : document.getElementById(iconId || 'themeToggleIcon');
        if (!liveIcon) {
            return;
        }
        liveIcon.classList.remove('lucide-sun', 'lucide-moon');
        liveIcon.setAttribute('data-lucide', isDark ? 'sun' : 'moon');
        liveIcon.classList.toggle('text-warning', isDark);
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    function bindDarkModeToggle(buttonEl, iconEl) {
        if (!buttonEl) {
            return;
        }
        updateToggleIcon(iconEl, isDarkMode());
        buttonEl.addEventListener('click', function () {
            updateToggleIcon(document.getElementById(iconEl?.id || 'themeToggleIcon'), toggleDarkMode());
        });
    }

    function applyThemeColor(color, options) {
        const opts = options || {};
        if (!VALID_THEMES.includes(color)) {
            return;
        }
        document.documentElement.setAttribute('data-theme-color', color);
        if (opts.persist) {
            localStorage.setItem('theme-color', color);
        }
        syncMetaThemeColor();
        document.dispatchEvent(new CustomEvent('theme-color-change', {
            detail: {
                color: color,
                primary: getThemePrimary(),
                accent: getThemeAccent(),
            },
        }));
    }

    function initThemeColor(config) {
        const cfg = config || {};
        const serverTheme = VALID_THEMES.includes(cfg.serverTheme) ? cfg.serverTheme : 'purple';
        const scope = cfg.scope || 'dashboard';
        let activeTheme = serverTheme;

        if (scope === 'dashboard') {
            const stored = localStorage.getItem('theme-color');
            activeTheme = VALID_THEMES.includes(stored) ? stored : serverTheme;
        }

        document.documentElement.setAttribute('data-theme-color', activeTheme);
    }

    function init(config) {
        initThemeColor(config || {});
        initDarkMode();
        syncMetaThemeColor();
    }

    function syncStoredThemeFromServer(serverTheme) {
        if (VALID_THEMES.includes(serverTheme)) {
            localStorage.setItem('theme-color', serverTheme);
        }
    }

    function getSwalTheme() {
        return {
            confirmButtonColor: getThemePrimary(),
            cancelButtonColor: '#64748b',
            background: isDarkMode() ? (readCssVar('--sp-card-bg') || '#111827') : '#ffffff',
            color: isDarkMode() ? '#f8fafc' : '#0f172a',
        };
    }

    function mergeSwalOptions(options) {
        return Object.assign({}, getSwalTheme(), options || {});
    }

    window.SpTheme = {
        VALID_THEMES: VALID_THEMES,
        getThemePrimary: getThemePrimary,
        getThemeAccent: getThemeAccent,
        getThemePrimaryRgb: getThemePrimaryRgb,
        isDarkMode: isDarkMode,
        initDarkMode: initDarkMode,
        setDarkMode: setDarkMode,
        toggleDarkMode: toggleDarkMode,
        bindDarkModeToggle: bindDarkModeToggle,
        updateToggleIcon: updateToggleIcon,
        syncMetaThemeColor: syncMetaThemeColor,
        applyThemeColor: applyThemeColor,
        initThemeColor: initThemeColor,
        init: init,
        syncStoredThemeFromServer: syncStoredThemeFromServer,
        getSwalTheme: getSwalTheme,
        mergeSwalOptions: mergeSwalOptions,
    };
})(window);
