(function () {
    'use strict';

    function enhanceEmptyTables() {
        document.querySelectorAll('.table tbody tr').forEach(function (row) {
            var cells = row.querySelectorAll('td');
            if (cells.length !== 1) return;
            var text = (cells[0].textContent || '').trim().toLowerCase();
            if (text.includes('belum ada') || text.includes('tidak ada data') || text.includes('data kosong')) {
                cells[0].classList.add('ak-empty-state');
            }
        });
    }

    function setupMenuSearch() {
        var input = document.getElementById('sidebarMenuSearch');
        if (!input) return;

        var items = Array.from(document.querySelectorAll('.sidebar-menu .menu-item'));
        var headers = Array.from(document.querySelectorAll('.sidebar-menu .menu-header'));
        var groups = Array.from(document.querySelectorAll('.sidebar-menu .menu-item.has-submenu'));

        function filterMenu() {
            var keyword = input.value.trim().toLocaleLowerCase('id');
            items.forEach(function (item) {
                var visible = !keyword || (item.textContent || '').toLocaleLowerCase('id').includes(keyword);
                item.classList.toggle('ak-filter-empty', !visible);
            });
            groups.forEach(function (group) {
                var storedState = group.dataset.menuGroup ? localStorage.getItem('ak-sidebar-group-' + group.dataset.menuGroup) : null;
                var shouldOpen = keyword
                    ? true
                    : group.classList.contains('active') || storedState === 'open';
                if (!keyword && storedState === 'closed' && !group.classList.contains('active')) {
                    shouldOpen = false;
                }
                group.classList.toggle('open', shouldOpen);
                var toggle = group.querySelector('.submenu-toggle');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', group.classList.contains('open') ? 'true' : 'false');
                }
            });

            headers.forEach(function (header) {
                var next = header.nextElementSibling;
                var hasVisibleItem = false;
                while (next && !next.classList.contains('menu-header')) {
                    if (next.classList.contains('menu-item') && !next.classList.contains('ak-filter-empty')) {
                        hasVisibleItem = true;
                        break;
                    }
                    next = next.nextElementSibling;
                }
                header.classList.toggle('ak-filter-empty', keyword && !hasVisibleItem);
            });
        }

        input.addEventListener('input', filterMenu);
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                input.value = '';
                filterMenu();
                input.blur();
            }
        });

        document.addEventListener('keydown', function (event) {
            var target = event.target;
            var isTyping = target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName);
            if (event.key === '/' && !isTyping && !event.metaKey && !event.ctrlKey && !event.altKey) {
                event.preventDefault();
                input.focus();
            }
        });
    }

    function setupSubmitFeedback() {
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.checkValidity()) return;
            if (form.dataset.noLoading === 'true' || form.dataset.confirm || event.submitter?.dataset.confirm) return;

            var button = event.submitter;
            if (!(button instanceof HTMLButtonElement)) return;

            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            if (!button.querySelector('.spinner-border')) {
                button.dataset.originalHtml = button.innerHTML;
                button.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>');
            }
        });
    }

    function improveTables() {
        document.querySelectorAll('.table-responsive').forEach(function (wrapper) {
            wrapper.setAttribute('tabindex', '0');
            wrapper.setAttribute('role', 'region');
            if (!wrapper.getAttribute('aria-label')) {
                wrapper.setAttribute('aria-label', 'Tabel data, dapat digulir secara horizontal');
            }
        });
    }

    function setPageBusyOnContextChange() {
        document.querySelectorAll('[data-auto-submit]').forEach(function (select) {
            select.addEventListener('change', function () {
                select.setAttribute('aria-busy', 'true');
                select.form?.submit();
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        enhanceEmptyTables();
        setupMenuSearch();
        setupSubmitFeedback();
        improveTables();
        setPageBusyOnContextChange();
    });
})();
