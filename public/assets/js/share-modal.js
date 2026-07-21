/**
 * SPMB WMVAA — Share Modal Handler
 * Version: 1.0
 *
 * Safely wires share buttons/modals when the related markup exists.
 * No-op on pages without share UI to avoid console errors.
 */
(function () {
    'use strict';

    function bindShareControls(root) {
        try {
            const scope = root || document;

            scope.querySelectorAll('[data-share-modal]').forEach(function (trigger) {
                if (!trigger) return;
                if (trigger.dataset.shareModalBound === '1') {
                    return;
                }

                trigger.dataset.shareModalBound = '1';
                trigger.addEventListener('click', function (event) {
                    const targetSelector = trigger.getAttribute('data-share-modal')
                        || trigger.getAttribute('data-bs-target');

                    if (!targetSelector) {
                        return;
                    }

                    const modalEl = document.querySelector(targetSelector);
                    if (!modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
                        return;
                    }

                    event.preventDefault();
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                });
            });

            scope.querySelectorAll('[data-share-action="copy"]').forEach(function (button) {
                if (!button) return;
                if (button.dataset.shareActionBound === '1') {
                    return;
                }

                button.dataset.shareActionBound = '1';
                button.addEventListener('click', async function () {
                    const url = button.getAttribute('data-share-url') || window.location.href;

                    try {
                        if (navigator.clipboard?.writeText) {
                            await navigator.clipboard.writeText(url);
                        }
                    } catch (error) {
                        console.warn('[ShareModal] Clipboard copy failed:', error);
                    }
                });
            });

            scope.querySelectorAll('[data-share-action="native"]').forEach(function (button) {
                if (!button) return;
                if (button.dataset.shareActionBound === '1') {
                    return;
                }

                button.dataset.shareActionBound = '1';
                button.addEventListener('click', async function () {
                    if (!navigator.share) {
                        return;
                    }

                    try {
                        await navigator.share({
                            title: button.getAttribute('data-share-title') || document.title,
                            text: button.getAttribute('data-share-text') || '',
                            url: button.getAttribute('data-share-url') || window.location.href,
                        });
                    } catch (error) {
                        if (error && error.name !== 'AbortError') {
                            console.warn('[ShareModal] Native share failed:', error);
                        }
                    }
                });
            });
        } catch (e) {
            console.warn('[ShareModal] bindShareControls error:', e);
        }
    }

    function initShareModal() {
        try {
            bindShareControls(document);

            const legacyShareButton = document.getElementById('shareButton');
            const legacyShareModal = document.getElementById('shareModal');

            if (legacyShareButton && legacyShareModal && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                if (legacyShareButton.dataset.shareModalBound !== '1') {
                    legacyShareButton.dataset.shareModalBound = '1';
                    legacyShareButton.addEventListener('click', function (event) {
                        event.preventDefault();
                        bootstrap.Modal.getOrCreateInstance(legacyShareModal).show();
                    });
                }
            }
        } catch (e) {
            console.warn('[ShareModal] Initialization error:', e);
        }
    }

    window.ShareModal = {
        init: initShareModal,
        bind: bindShareControls,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initShareModal);
    } else {
        setTimeout(initShareModal, 0);
    }
})();
