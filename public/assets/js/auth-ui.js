(function () {
    'use strict';

    function initializeIcons() {
        if (window.lucide) window.lucide.createIcons();
    }

    function setupPasswordToggles() {
        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var target = document.getElementById(button.dataset.passwordToggle);
                if (!target) return;
                var show = target.type === 'password';
                target.type = show ? 'text' : 'password';
                button.setAttribute('aria-pressed', String(show));
                button.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
                button.innerHTML = '<i data-lucide="' + (show ? 'eye-off' : 'eye') + '"></i>';
                initializeIcons();
                target.focus();
            });
        });
    }

    function setupSubmitState() {
        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () {
                if (!form.checkValidity()) return;
                var button = form.querySelector('button[type="submit"]');
                if (!button) return;
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
                button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Memverifikasi...</span>';
            });
        });
    }

    function setupPasswordStrength() {
        var input = document.getElementById('new_password');
        var confirmation = document.getElementById('confirm_password');
        var progress = document.querySelector('[data-password-progress]');
        if (!input || !progress) return;

        var rules = {
            length: function (value) { return value.length >= 12; },
            upper: function (value) { return /[A-Z]/.test(value); },
            lower: function (value) { return /[a-z]/.test(value); },
            number: function (value) { return /\d/.test(value); },
            symbol: function (value) { return /[^A-Za-z0-9]/.test(value); }
        };

        function refresh() {
            var value = input.value;
            var score = 0;
            Object.keys(rules).forEach(function (name) {
                var valid = rules[name](value);
                score += valid ? 1 : 0;
                document.querySelector('[data-password-rule="' + name + '"]')?.classList.toggle('valid', valid);
            });
            progress.dataset.score = String(score);
            var label = progress.nextElementSibling;
            if (label) label.textContent = ['Belum diisi', 'Sangat lemah', 'Lemah', 'Cukup', 'Kuat', 'Sangat kuat'][score];

            if (confirmation?.value) {
                confirmation.setCustomValidity(confirmation.value === value ? '' : 'Konfirmasi password belum sama.');
            }
        }

        input.addEventListener('input', refresh);
        confirmation?.addEventListener('input', refresh);
    }

    document.addEventListener('DOMContentLoaded', function () {
        initializeIcons();
        setupPasswordToggles();
        setupSubmitState();
        setupPasswordStrength();
    });
})();
