/**
 * SPMB WMVAA — Dashboard Mobile Optimization
 * Phase 1: Mobile Experience - Task #5
 * File: mobile-dashboard-optimizer.js
 * Version: 1.0
 *
 * Purpose: Auto-convert tables to cards on mobile, manage responsive layouts
 * Features: Table→Card conversion, drawer navigation, responsive columns
 */

class MobileDashboardOptimizer {
    constructor() {
        this.breakpoint = 768;
        this.isMobile = window.innerWidth < this.breakpoint;
        this.tableConfigs = new Map();
        this.init();
    }

    /**
     * Initialize dashboard mobile optimizer
     */
    init() {
        this.setupEventListeners();
        this.processAllTables();
        this.enhanceCards();
        this.setupNavigationDrawer();
        window.addEventListener('resize', () => this.handleResize());
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            this.processAllTables();
        });
    }

    /**
     * Process all tables and add mobile-friendly attributes
     */
    processAllTables() {
        const tables = document.querySelectorAll('table');
        tables.forEach((table, index) => {
            this.enhanceTable(table, index);
        });
    }

    /**
     * Enhance table with mobile attributes
     * @param {HTMLTableElement} table
     * @param {number} index
     */
    enhanceTable(table, index) {
        const headers = Array.from(table.querySelectorAll('thead th')).map(th =>
            th.textContent.trim()
        );

        // Add data-label to each cell
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach((row, rowIndex) => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, cellIndex) => {
                if (headers[cellIndex]) {
                    cell.setAttribute('data-label', headers[cellIndex]);
                }

                // Identify action cells
                if (this.isActionCell(cell)) {
                    cell.classList.add('action-cell');
                }
            });
        });

        // Store config for reference
        this.tableConfigs.set(`table-${index}`, {
            element: table,
            headers: headers,
            rowCount: rows.length
        });
    }

    /**
     * Check if cell contains actions (buttons, links)
     * @param {HTMLTableCellElement} cell
     * @returns {boolean}
     */
    isActionCell(cell) {
        return Boolean(
            cell.querySelector('a, button, .btn, [role="button"]')
        );
    }

    /**
     * Enhance stat cards with click handling
     */
    enhanceCards() {
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => {
            // Add focus visible state
            card.addEventListener('focusin', () => {
                card.setAttribute('tabindex', '0');
            });

            // Keyboard navigation
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    const link = card.querySelector('a');
                    if (link) link.click();
                }
            });
        });
    }

    /**
     * Setup mobile navigation drawer
     */
    setupNavigationDrawer() {
        // Create toggle button if it doesn't exist
        const toggle = document.querySelector('.dashboard-nav-toggle');
        if (!toggle) return;

        toggle.addEventListener('click', () => this.toggleDrawer());

        // Close drawer when clicking outside
        document.addEventListener('click', (e) => {
            const drawer = document.querySelector('.dashboard-sidenav, .dashboard-sidebar');
            if (drawer && !drawer.contains(e.target) && !toggle.contains(e.target)) {
                this.closeDrawer();
            }
        });

        // Close button
        const closeBtn = document.querySelector('.dashboard-sidenav__close, .dashboard-sidebar__close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeDrawer());
        }
    }

    /**
     * Toggle navigation drawer
     */
    toggleDrawer() {
        const drawer = document.querySelector('.dashboard-sidenav, .dashboard-sidebar');
        if (drawer) {
            drawer.classList.toggle('active');
        }
    }

    /**
     * Close navigation drawer
     */
    closeDrawer() {
        const drawer = document.querySelector('.dashboard-sidenav, .dashboard-sidebar');
        if (drawer) {
            drawer.classList.remove('active');
        }
    }

    /**
     * Handle window resize
     */
    handleResize() {
        const wasMobile = this.isMobile;
        this.isMobile = window.innerWidth < this.breakpoint;

        if (wasMobile !== this.isMobile) {
            // Transition between mobile and desktop
            if (!this.isMobile) {
                this.closeDrawer();
            }
            document.body.classList.toggle('is-mobile', this.isMobile);
        }
    }

    /**
     * Add row selection for bulk actions
     * @param {string} tableSelector
     */
    addRowSelection(tableSelector) {
        const table = document.querySelector(tableSelector);
        if (!table) return;

        const thead = table.querySelector('thead');
        const tbody = table.querySelector('tbody');

        // Add checkbox to header
        const headerRow = thead.querySelector('tr');
        const headerCell = document.createElement('th');
        headerCell.innerHTML = '<input type="checkbox" class="select-all" style="width: 44px; height: 44px;">';
        headerRow.insertBefore(headerCell, headerRow.firstChild);

        // Add checkbox to each row
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(row => {
            const cell = document.createElement('td');
            cell.innerHTML = '<input type="checkbox" class="select-row" style="width: 44px; height: 44px;">';
            cell.style.minHeight = '44px';
            row.insertBefore(cell, row.firstChild);
        });

        // Select all functionality
        const selectAllCheckbox = headerCell.querySelector('input');
        selectAllCheckbox.addEventListener('change', (e) => {
            rows.forEach(row => {
                row.querySelector('input.select-row').checked = e.target.checked;
            });
        });
    }

    /**
     * Make stat card clickable with navigation
     * @param {string} cardSelector
     * @param {string} targetUrl
     */
    makeCardClickable(cardSelector, targetUrl) {
        const card = document.querySelector(cardSelector);
        if (!card) return;

        card.style.cursor = 'pointer';
        card.addEventListener('click', () => {
            window.location.href = targetUrl;
        });
    }

    /**
     * Sort table (for mobile list view)
     * @param {string} tableSelector
     * @param {number} columnIndex
     */
    sortTable(tableSelector, columnIndex) {
        const table = document.querySelector(tableSelector);
        if (!table) return;

        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        rows.sort((a, b) => {
            const aValue = a.querySelectorAll('td')[columnIndex]?.textContent.trim();
            const bValue = b.querySelectorAll('td')[columnIndex]?.textContent.trim();
            return aValue.localeCompare(bValue);
        });

        rows.forEach(row => tbody.appendChild(row));
    }

    /**
     * Filter table rows based on search term
     * @param {string} tableSelector
     * @param {string} searchTerm
     * @param {number[]} columnIndices - which columns to search
     */
    filterTable(tableSelector, searchTerm, columnIndices = []) {
        const table = document.querySelector(tableSelector);
        if (!table) return;

        const tbody = table.querySelector('tbody');
        const rows = tbody.querySelectorAll('tr');
        const term = searchTerm.toLowerCase();

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            let found = false;

            if (columnIndices.length === 0) {
                // Search all columns
                found = row.textContent.toLowerCase().includes(term);
            } else {
                // Search specific columns
                columnIndices.forEach(index => {
                    if (cells[index]?.textContent.toLowerCase().includes(term)) {
                        found = true;
                    }
                });
            }

            row.style.display = found ? '' : 'none';
        });
    }

    /**
     * Clear table filter
     * @param {string} tableSelector
     */
    clearTableFilter(tableSelector) {
        const table = document.querySelector(tableSelector);
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.style.display = '';
        });
    }

    /**
     * Add load more functionality for long lists
     * @param {string} tableSelector
     * @param {number} rowsPerPage
     */
    addLoadMore(tableSelector, rowsPerPage = 10) {
        const table = document.querySelector(tableSelector);
        if (!table) return;

        const tbody = table.querySelector('tbody');
        const rows = tbody.querySelectorAll('tr');

        if (rows.length <= rowsPerPage) return;

        // Hide extra rows
        rows.forEach((row, index) => {
            if (index >= rowsPerPage) {
                row.style.display = 'none';
                row.dataset.hidden = 'true';
            }
        });

        // Create load more button
        const loadMoreBtn = document.createElement('button');
        loadMoreBtn.className = 'btn btn-outline-primary btn-block mt-3';
        loadMoreBtn.textContent = `Tampilkan lebih banyak (${rows.length - rowsPerPage} lainnya)`;
        loadMoreBtn.addEventListener('click', () => {
            rows.forEach(row => {
                if (row.dataset.hidden === 'true') {
                    row.style.display = '';
                    row.dataset.hidden = 'false';
                }
            });
            loadMoreBtn.remove();
        });

        table.parentNode.insertBefore(loadMoreBtn, table.nextSibling);
    }

    /**
     * Ensure touch targets are large enough
     */
    auditTouchTargets() {
        const elements = document.querySelectorAll('button, a, input[type="checkbox"], input[type="radio"]');
        const issues = [];

        elements.forEach(element => {
            const rect = element.getBoundingClientRect();
            const width = rect.width;
            const height = rect.height;

            // 44x44px is WCAG AA standard
            if (width < 44 || height < 44) {
                issues.push({
                    element: element,
                    width: width,
                    height: height,
                    selector: this.getElementSelector(element)
                });
            }
        });

        return issues;
    }

    /**
     * Get CSS selector for element (for debugging)
     * @param {HTMLElement} element
     * @returns {string}
     */
    getElementSelector(element) {
        if (element.id) return `#${element.id}`;
        if (element.className) {
            const classes = Array.from(element.classList).join('.');
            return `.${classes}`;
        }
        return element.tagName.toLowerCase();
    }

    /**
     * Get dashboard stats for monitoring
     * @returns {object}
     */
    getStats() {
        return {
            isMobile: this.isMobile,
            screenWidth: window.innerWidth,
            screenHeight: window.innerHeight,
            tables: this.tableConfigs.size,
            statCards: document.querySelectorAll('.stat-card').length,
            touchTargetIssues: this.auditTouchTargets().length
        };
    }
}

// Initialize on load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.mobileDashboard = new MobileDashboardOptimizer();
    });
} else {
    window.mobileDashboard = new MobileDashboardOptimizer();
}

// Export for testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MobileDashboardOptimizer;
}
