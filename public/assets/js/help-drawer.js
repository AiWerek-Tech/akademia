/**
 * SPMB WMVAA - Context-Aware Help Drawer Controller
 * Handles loading help articles dynamically and rendering in offcanvas drawer.
 */
$(document).ready(function() {
    // 1. Detect active page route to select correct article
    function getActiveRouteKey() {
        const path = window.location.pathname;
        
        // Match pendaftar routes
        if (path.includes('/pendaftar/dashboard') || (path.endsWith('/pendaftar') && !path.includes('/pendaftar/'))) {
            return 'pendaftar/dashboard';
        }
        if (path.includes('/pendaftar/daftar') || path.includes('/pendaftar/formulir')) {
            return 'pendaftar/daftar';
        }
        if (path.includes('/pendaftar/dokumen') || path.includes('/pendaftar/berkas')) {
            return 'pendaftar/dokumen';
        }
        if (path.includes('/pendaftar/account-card')) {
            return 'pendaftar/account-card';
        }

        // Match operator/panitia routes
        if (path.includes('/operator/registrants/walk-in')) {
            return 'operator/walk-in';
        }
        if (path.includes('/operator/documents') || path.includes('/operator/registrants')) {
            return 'operator/registrants';
        }
        if (path.includes('/operator/dapodik')) {
            return 'operator/dapodik';
        }
        
        // Match bendahara routes
        if (path.includes('/bendahara/invoices') || path.includes('/bendahara/payments')) {
            // Check if it's detail page (ends with a number or segment after invoices)
            const parts = path.split('/');
            const lastSegment = parts[parts.length - 1];
            if (!isNaN(lastSegment) && lastSegment.trim() !== '') {
                return 'bendahara/invoices/detail';
            }
            return 'bendahara/invoices';
        }
        if (path.includes('/bendahara/dashboard')) {
            return 'bendahara/dashboard';
        }
        if (path.includes('/bendahara/daily-recap')) {
            return 'bendahara/daily-recap';
        }
        if (path.includes('/bendahara/reports')) {
            return 'bendahara/reports';
        }
        if (path.includes('/bendahara/cash-drawers')) {
            return 'bendahara/cash-drawers';
        }
        if (path.includes('/bendahara/quick-payment')) {
            return 'bendahara/quick-payment';
        }
        
        // Match admin routes
        if (path.includes('/admin/dashboard') || path.endsWith('/admin') || path.includes('/admin/smp') || path.includes('/admin/sma')) {
            return 'admin/dashboard';
        }
        if (path.includes('/admin/access')) {
            return 'admin/access';
        }
        if (path.includes('/admin/settings')) {
            return 'admin/settings';
        }
        if (path.includes('/admin/fee-types') || path.includes('/admin/fee-type')) {
            return 'admin/fee-types';
        }
        if (path.includes('/admin/academic-years') || path.includes('/admin/academic-year') || path.includes('/admin/gelombang')) {
            return 'admin/academic-years';
        }
        
        return null;
    }

    // 2. Render help article in offcanvas body
    window.loadHelpArticle = function(routeKey) {
        const contentArea = $('#helpDrawerContent');
        if (!contentArea.length) return;

        const article = (routeKey && HelpArticles[routeKey]) ? HelpArticles[routeKey] : DefaultHelpArticle;
        const currentRoute = getActiveRouteKey();

        let headerHtml = `
            <div class="mb-4">
                <h5 class="fw-bold text-dark mb-1">${article.title}</h5>
                <p class="text-muted small mb-0">${article.description}</p>
            </div>
            <hr class="my-3">
        `;

        let bodyHtml = `
            <div class="help-article-body">
                ${article.content}
            </div>
        `;

        let footerHtml = '';
        
        // Show index/directory buttons
        if (routeKey === 'index') {
            footerHtml = `
                <div class="mt-4 pt-3 border-top text-center">
                    <button type="button" class="btn btn-sm btn-outline-primary w-100 py-2" onclick="loadHelpArticle('${currentRoute}')">
                        <i data-lucide="arrow-left" class="me-1" style="width:14px;height:14px;"></i> Kembali ke Panduan Halaman Ini
                    </button>
                </div>
            `;
        } else {
            footerHtml = `
                <div class="mt-4 pt-3 border-top text-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary w-100 py-2" onclick="loadHelpIndex()">
                        <i data-lucide="book-open" class="me-1" style="width:14px;height:14px;"></i> Lihat Semua Panduan
                    </button>
                </div>
            `;
        }

        contentArea.html(headerHtml + bodyHtml + footerHtml);

        // Recreate Lucide Icons to make sure icon vectors inside loaded help content display correctly
        if (typeof lucide !== 'undefined') {
            try { lucide.createIcons(); } catch(e) {}
        }
    };

    // 3. Load directory of all help articles
    window.loadHelpIndex = function() {
        const contentArea = $('#helpDrawerContent');
        if (!contentArea.length) return;

        let indexHtml = `
            <div class="list-group list-group-flush">
        `;

        // Gather all categories
        const pendaftarList = [];
        const operatorList = [];
        const bendaharaList = [];
        const adminList = [];

        Object.keys(HelpArticles).forEach(key => {
            if (key.startsWith('pendaftar/')) pendaftarList.push(key);
            else if (key.startsWith('operator/')) operatorList.push(key);
            else if (key.startsWith('bendahara/')) bendaharaList.push(key);
            else if (key.startsWith('admin/')) adminList.push(key);
        });

        // 3a. Pendaftar group
        indexHtml += `<div class="fw-bold small text-primary text-uppercase mt-2 mb-2">Panduan Calon Siswa</div>`;
        pendaftarList.forEach(key => {
            const art = HelpArticles[key];
            indexHtml += `
                <a href="javascript:void(0)" class="list-group-item list-group-item-action border-0 px-2 py-2 rounded mb-1" onclick="loadHelpArticle('${key}')">
                    <div class="fw-semibold text-dark small"><i data-lucide="file-text" class="me-1 text-muted" style="width:14px;height:14px;vertical-align:middle;"></i> ${art.title}</div>
                </a>
            `;
        });

        // 3b. Operator/Panitia group
        indexHtml += `<div class="fw-bold small text-primary text-uppercase mt-4 mb-2">Panduan Panitia / Operator</div>`;
        operatorList.forEach(key => {
            const art = HelpArticles[key];
            indexHtml += `
                <a href="javascript:void(0)" class="list-group-item list-group-item-action border-0 px-2 py-2 rounded mb-1" onclick="loadHelpArticle('${key}')">
                    <div class="fw-semibold text-dark small"><i data-lucide="users" class="me-1 text-muted" style="width:14px;height:14px;vertical-align:middle;"></i> ${art.title}</div>
                </a>
            `;
        });

        // 3c. Bendahara group
        indexHtml += `<div class="fw-bold small text-primary text-uppercase mt-4 mb-2">Panduan Bendahara / Keuangan</div>`;
        bendaharaList.forEach(key => {
            const art = HelpArticles[key];
            indexHtml += `
                <a href="javascript:void(0)" class="list-group-item list-group-item-action border-0 px-2 py-2 rounded mb-1" onclick="loadHelpArticle('${key}')">
                    <div class="fw-semibold text-dark small"><i data-lucide="landmark" class="me-1 text-muted" style="width:14px;height:14px;vertical-align:middle;"></i> ${art.title}</div>
                </a>
            `;
        });

        // 3d. Admin group
        indexHtml += `<div class="fw-bold small text-primary text-uppercase mt-4 mb-2">Panduan Administrator</div>`;
        adminList.forEach(key => {
            const art = HelpArticles[key];
            indexHtml += `
                <a href="javascript:void(0)" class="list-group-item list-group-item-action border-0 px-2 py-2 rounded mb-1" onclick="loadHelpArticle('${key}')">
                    <div class="fw-semibold text-dark small"><i data-lucide="settings" class="me-1 text-muted" style="width:14px;height:14px;vertical-align:middle;"></i> ${art.title}</div>
                </a>
            `;
        });

        indexHtml += `
            </div>
        `;

        const currentRoute = getActiveRouteKey();
        const footerHtml = `
            <div class="mt-4 pt-3 border-top text-center">
                <button type="button" class="btn btn-sm btn-outline-primary w-100 py-2" onclick="loadHelpArticle('${currentRoute}')">
                    <i data-lucide="arrow-left" class="me-1" style="width:14px;height:14px;"></i> Kembali ke Panduan Halaman Ini
                </button>
            </div>
        `;

        const headerHtml = `
            <div class="mb-4">
                <h5 class="fw-bold text-dark mb-1">Daftar Isi Bantuan</h5>
                <p class="text-muted small mb-0">Silakan pilih topik bantuan yang Anda butuhkan di bawah ini.</p>
            </div>
            <hr class="my-3">
        `;

        contentArea.html(headerHtml + indexHtml + footerHtml);

        if (typeof lucide !== 'undefined') {
            try { lucide.createIcons(); } catch(e) {}
        }
    };

    // 4. Bind events to Bantuan buttons/links
    $(document).on('click', '.trigger-help-drawer', function(e) {
        e.preventDefault();
        
        // Open offcanvas drawer via Bootstrap instances API
        const helpElement = document.getElementById('helpDrawer');
        if (helpElement) {
            const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(helpElement);
            
            // Load correct article before showing
            const activeRoute = getActiveRouteKey();
            loadHelpArticle(activeRoute);
            
            bsOffcanvas.show();
        }
    });
});
