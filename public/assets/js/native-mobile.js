(function () {
    'use strict';

    function closeMobileMenu() {
        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('sidebar-overlay');
        sidebar?.classList.remove('active');
        overlay?.classList.remove('active');
        document.body.classList.remove('mobile-menu-open');
    }

    function openMobileMenu() {
        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('sidebar-overlay');
        sidebar?.classList.add('active');
        overlay?.classList.add('active');
        document.body.classList.add('mobile-menu-open');
        window.setTimeout(function () { sidebar?.querySelector('input,button,a')?.focus(); }, 180);
    }

    function setupMobileNavigation() {
        document.querySelectorAll('[data-mobile-menu]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (document.getElementById('sidebar')?.classList.contains('active')) closeMobileMenu();
                else openMobileMenu();
            });
        });
        document.getElementById('sidebar-overlay')?.addEventListener('click', closeMobileMenu);
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeMobileMenu();
        });
    }

    function setupStandaloneLinks() {
        document.querySelectorAll('a[target="_blank"]').forEach(function (link) {
            if (!window.matchMedia('(display-mode: standalone)').matches) return;
            if (link.origin === window.location.origin) link.removeAttribute('target');
        });
    }

    function setupLogoutPrivacy() {
        var logout = document.getElementById('logoutForm');
        logout?.addEventListener('submit', function () {
            navigator.serviceWorker?.controller?.postMessage({type: 'CLEAR_RUNTIME_CACHE'});
        });
    }

    function setupPressFeedback() {
        document.addEventListener('pointerdown', function (event) {
            var target = event.target.closest('.dashboard-bottom-item,.native-action-card,.btn');
            if (target) target.classList.add('is-pressed');
        }, {passive: true});
        ['pointerup', 'pointercancel'].forEach(function (name) {
            document.addEventListener(name, function () {
                document.querySelectorAll('.is-pressed').forEach(function (item) { item.classList.remove('is-pressed'); });
            }, {passive: true});
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        setupMobileNavigation();
        setupStandaloneLinks();
        setupLogoutPrivacy();
        setupPressFeedback();
    });
})();
