(function () {
    'use strict';

    var deferredInstallPrompt = null;
    var installButtons = [];

    function setStandaloneClass() {
        var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        document.documentElement.classList.toggle('pwa-standalone', standalone);
        document.body?.classList.toggle('pwa-standalone', standalone);
        return standalone;
    }

    function updateInstallButtons(ready) {
        installButtons = Array.from(document.querySelectorAll('[data-pwa-install]'));
        installButtons.forEach(function (button) {
            button.hidden = !ready || setStandaloneClass();
        });
    }

    function showConnectionStatus(online) {
        var status = document.getElementById('appConnectionStatus');
        if (!status) {
            status = document.createElement('div');
            status.id = 'appConnectionStatus';
            status.className = 'app-connection-status';
            status.setAttribute('role', 'status');
            status.setAttribute('aria-live', 'polite');
            document.body.appendChild(status);
        }
        status.className = 'app-connection-status ' + (online ? 'is-online' : 'is-offline');
        status.innerHTML = '<span></span>' + (online ? 'Koneksi kembali tersedia' : 'Mode offline · data terbaru belum dapat dimuat');
        status.classList.add('is-visible');
        if (online) window.setTimeout(function () { status.classList.remove('is-visible'); }, 3500);
    }

    function bindInstallButtons() {
        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-pwa-install]');
            if (!button || !deferredInstallPrompt) return;
            deferredInstallPrompt.prompt();
            deferredInstallPrompt.userChoice.finally(function () {
                deferredInstallPrompt = null;
                updateInstallButtons(false);
            });
        });
    }

    function showUpdate(registration) {
        if (document.getElementById('pwaUpdateNotice')) return;
        var notice = document.createElement('div');
        notice.id = 'pwaUpdateNotice';
        notice.className = 'pwa-update-notice';
        notice.innerHTML = '<div><strong>Pembaruan aplikasi tersedia</strong><span>Muat versi terbaru tanpa keluar dari akun.</span></div><button type="button">Perbarui</button>';
        notice.querySelector('button').addEventListener('click', function () {
            registration.waiting?.postMessage({type: 'SKIP_WAITING'});
        });
        document.body.appendChild(notice);
    }

    function registerServiceWorker() {
        if (!('serviceWorker' in navigator) || !window.isSecureContext) return;
        var script = document.documentElement.dataset.serviceWorker;
        if (!script) return;
        navigator.serviceWorker.register(script).then(function (registration) {
            if (registration.waiting) showUpdate(registration);
            registration.addEventListener('updatefound', function () {
                var worker = registration.installing;
                worker?.addEventListener('statechange', function () {
                    if (worker.state === 'installed' && navigator.serviceWorker.controller) showUpdate(registration);
                });
            });
        }).catch(function () {
            // PWA remains optional; normal authenticated navigation still works.
        });
        navigator.serviceWorker.addEventListener('controllerchange', function () { window.location.reload(); });
    }

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        deferredInstallPrompt = event;
        updateInstallButtons(true);
    });
    window.addEventListener('appinstalled', function () { deferredInstallPrompt = null; updateInstallButtons(false); });
    window.addEventListener('online', function () { showConnectionStatus(true); });
    window.addEventListener('offline', function () { showConnectionStatus(false); });

    document.addEventListener('DOMContentLoaded', function () {
        setStandaloneClass();
        updateInstallButtons(Boolean(deferredInstallPrompt));
        bindInstallButtons();
        registerServiceWorker();
        if (!navigator.onLine) showConnectionStatus(false);
    });
})();
