/**
 * SPMB WMVAA — Touch Target Sizing Audit
 * Phase 1: Mobile Experience - Task #6
 * File: touch-target-auditor.js
 * Version: 1.0
 *
 * Purpose: Audit and report touch target sizes across the application
 * Features: Detect small buttons, interactive elements, generate report
 */

window.TouchTargetAuditor = window.TouchTargetAuditor || class TouchTargetAuditor {
    constructor(options = {}) {
        this.options = {
            minSize: 44,  // WCAG AA standard (16mm physical)
            ignoreSelectors: ['.hidden', '.d-none', '[style*="display: none"]'],
            reportToConsole: true,
            highlightIssues: false,
            ...options
        };

        this.issues = [];
        this.totalElements = 0;
        this.dpi = this.detectDPI();
    }

    /**
     * Detect screen DPI for accurate physical size calculation
     */
    detectDPI() {
        const div = document.createElement('div');
        div.style.width = '1in';
        div.style.height = '1in';
        div.style.position = 'absolute';
        div.style.left = '-9999px';
        document.body.appendChild(div);
        
        const dpi = div.offsetWidth;
        document.body.removeChild(div);
        
        return dpi;
    }

    /**
     * Audit all interactive elements on the page
     */
    audit() {
        const selectors = [
            'button',
            'a[href]',
            'input[type="button"]',
            'input[type="submit"]',
            'input[type="reset"]',
            'input[type="checkbox"]',
            'input[type="radio"]',
            'input[type="file"]',
            '[role="button"]',
            '[role="checkbox"]',
            '[role="radio"]',
            '[role="tab"]',
            '[role="link"]',
            '.btn',
            '.clickable'
        ];

        const elements = new Set();

        selectors.forEach(selector => {
            try {
                document.querySelectorAll(selector).forEach(el => {
                    if (this.isVisible(el) && !this.shouldIgnore(el)) {
                        elements.add(el);
                    }
                });
            } catch (e) {
                console.warn(`Invalid selector: ${selector}`, e);
            }
        });

        this.totalElements = elements.size;

        elements.forEach(element => {
            this.checkElement(element);
        });

        if (this.options.reportToConsole) {
            this.printReport();
        }

        if (this.options.highlightIssues) {
            this.highlightProblems();
        }

        return {
            totalElements: this.totalElements,
            issuesFound: this.issues.length,
            compliant: this.issues.length === 0,
            issues: this.issues,
            summary: this.getSummary()
        };
    }

    /**
     * Check if element is visible
     */
    isVisible(element) {
        if (!element) return false;
        
        const style = window.getComputedStyle(element);
        return style.display !== 'none' && 
               style.visibility !== 'hidden' && 
               style.opacity !== '0';
    }

    /**
     * Check if element should be ignored
     */
    shouldIgnore(element) {
        return this.options.ignoreSelectors.some(selector => {
            try {
                return element.matches(selector);
            } catch (e) {
                return false;
            }
        });
    }

    /**
     * Check single element's touch target size
     */
    checkElement(element) {
        const rect = element.getBoundingClientRect();
        const width = Math.round(rect.width);
        const height = Math.round(rect.height);
        const minSize = this.options.minSize;

        // Check if either dimension is too small
        if (width < minSize || height < minSize) {
            this.issues.push({
                element: element,
                selector: this.getElementSelector(element),
                width: width,
                height: height,
                text: element.textContent?.trim().substring(0, 50) || '',
                type: this.getElementType(element),
                severity: this.calculateSeverity(width, height, minSize),
                location: {
                    x: Math.round(rect.left),
                    y: Math.round(rect.top)
                }
            });
        }
    }

    /**
     * Get CSS selector for element
     */
    getElementSelector(element) {
        if (element.id) return `#${element.id}`;
        
        const classes = Array.from(element.classList)
            .filter(c => !c.match(/^(show|hide|d-|w-|h-|m-|p-|col-)/))
            .slice(0, 2);
        
        if (classes.length > 0) {
            return `.${classes.join('.')}`;
        }

        let path = [];
        while (element.parentElement) {
            const index = Array.from(element.parentElement.children)
                .filter(el => el.tagName === element.tagName)
                .indexOf(element);
            
            path.unshift(`${element.tagName.toLowerCase()}${index > 0 ? `:nth-of-type(${index + 1})` : ''}`);
            element = element.parentElement;
        }

        return path.join(' > ');
    }

    /**
     * Get element type for reporting
     */
    getElementType(element) {
        if (element.tagName === 'BUTTON') return 'button';
        if (element.tagName === 'A') return 'link';
        if (element.tagName === 'INPUT') return `input[${element.type}]`;
        
        const role = element.getAttribute('role');
        if (role) return `[role="${role}"]`;

        if (element.classList.contains('btn')) return 'btn';
        return 'interactive';
    }

    /**
     * Calculate severity level
     */
    calculateSeverity(width, height, minSize) {
        const minDim = Math.min(width, height);
        const ratio = minDim / minSize;

        if (ratio < 0.5) return 'critical';
        if (ratio < 0.75) return 'high';
        return 'medium';
    }

    /**
     * Get audit summary
     */
    getSummary() {
        const criticalCount = this.issues.filter(i => i.severity === 'critical').length;
        const highCount = this.issues.filter(i => i.severity === 'high').length;
        const mediumCount = this.issues.filter(i => i.severity === 'medium').length;

        const compliance = ((this.totalElements - this.issues.length) / this.totalElements * 100).toFixed(1);

        return {
            totalElements: this.totalElements,
            compliant: this.totalElements - this.issues.length,
            nonCompliant: this.issues.length,
            compliancePercent: parseFloat(compliance),
            criticalIssues: criticalCount,
            highIssues: highCount,
            mediumIssues: mediumCount
        };
    }

    /**
     * Print audit report to console
     */
    printReport() {
        const summary = this.getSummary();

        console.group('🎯 Touch Target Sizing Audit Report');
        console.log('═══════════════════════════════════════');

        // Summary stats
        console.group('📊 Summary');
        console.log(`Total interactive elements: ${summary.totalElements}`);
        console.log(`Compliant (≥44×44px): ${summary.compliant}`);
        console.log(`Non-compliant: ${summary.nonCompliant}`);
        console.log(`Compliance: ${summary.compliancePercent}%`);
        console.log(`—`);
        console.log(`🔴 Critical: ${summary.criticalIssues}`);
        console.log(`🟠 High: ${summary.highIssues}`);
        console.log(`🟡 Medium: ${summary.mediumIssues}`);
        console.groupEnd();

        // Detailed issues
        if (this.issues.length > 0) {
            console.group('⚠️  Issues Found');

            // Critical issues
            const critical = this.issues.filter(i => i.severity === 'critical');
            if (critical.length > 0) {
                console.group('🔴 Critical Issues (Fix Immediately)');
                critical.forEach(issue => this.printIssue(issue));
                console.groupEnd();
            }

            // High issues
            const high = this.issues.filter(i => i.severity === 'high');
            if (high.length > 0) {
                console.group('🟠 High Priority Issues');
                high.forEach(issue => this.printIssue(issue));
                console.groupEnd();
            }

            // Medium issues
            const medium = this.issues.filter(i => i.severity === 'medium');
            if (medium.length > 0) {
                console.group('🟡 Medium Priority Issues');
                medium.forEach(issue => this.printIssue(issue));
                console.groupEnd();
            }

            console.groupEnd();
        } else {
            console.log('✅ All interactive elements meet WCAG AA touch target requirements!');
        }

        console.log('═══════════════════════════════════════');
        console.groupEnd();
    }

    /**
     * Print single issue details
     */
    printIssue(issue) {
        console.log(`%c${issue.type}`, 'font-weight: bold; color: #ef4444;');
        console.log(`  Size: ${issue.width}×${issue.height}px (min: ${this.options.minSize}×${this.options.minSize}px)`);
        console.log(`  Selector: ${issue.selector}`);
        console.log(`  Text: "${issue.text}"`);
        console.log(`  Location: (${issue.location.x}, ${issue.location.y})`);
        console.log(`  Element:`, issue.element);
    }

    /**
     * Highlight problematic elements on page
     */
    highlightProblems() {
        this.issues.forEach(issue => {
            const element = issue.element;
            
            // Add visual highlight
            element.style.boxShadow = `0 0 3px 2px ${
                issue.severity === 'critical' ? '#ef4444' :
                issue.severity === 'high' ? '#f97316' :
                '#eab308'
            }`;
            
            // Add title attribute
            element.title = `Touch target too small: ${issue.width}×${issue.height}px (min: 44×44px)`;
        });

        console.log(`%c✓ Highlighted ${this.issues.length} non-compliant elements`, 'color: #3b82f6; font-weight: bold;');
    }

    /**
     * Generate HTML report
     */
    generateHTMLReport() {
        const summary = this.getSummary();
        const issues = this.issues;

        let html = `
            <div style="font-family: sans-serif; padding: 20px; max-width: 800px;">
                <h1>🎯 Touch Target Sizing Audit Report</h1>
                
                <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h2>Summary</h2>
                    <p><strong>Total Elements:</strong> ${summary.totalElements}</p>
                    <p><strong>Compliant:</strong> ${summary.compliant} (${summary.compliancePercent}%)</p>
                    <p><strong>Non-Compliant:</strong> ${summary.nonCompliant}</p>
                    <p style="margin-top: 10px; border-top: 1px solid #d1d5db; padding-top: 10px;">
                        <strong>🔴 Critical:</strong> ${summary.criticalIssues} | 
                        <strong>🟠 High:</strong> ${summary.highIssues} | 
                        <strong>🟡 Medium:</strong> ${summary.mediumIssues}
                    </p>
                </div>
        `;

        if (issues.length > 0) {
            html += '<h2>Issues Found</h2><table style="width: 100%; border-collapse: collapse;">';
            html += `
                <thead>
                    <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 8px; text-align: left;">Type</th>
                        <th style="padding: 8px; text-align: left;">Size</th>
                        <th style="padding: 8px; text-align: left;">Severity</th>
                        <th style="padding: 8px; text-align: left;">Selector</th>
                    </tr>
                </thead>
                <tbody>
            `;

            issues.forEach(issue => {
                const severityColor = {
                    critical: '#ef4444',
                    high: '#f97316',
                    medium: '#eab308'
                }[issue.severity];

                html += `
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 8px;">${issue.type}</td>
                        <td style="padding: 8px;">${issue.width}×${issue.height}px</td>
                        <td style="padding: 8px;">
                            <span style="color: ${severityColor}; font-weight: bold;">
                                ●${issue.severity.charAt(0).toUpperCase() + issue.severity.slice(1)}
                            </span>
                        </td>
                        <td style="padding: 8px; font-size: 0.85em; word-break: break-word;">
                            ${issue.selector}
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
        } else {
            html += '<p style="color: #22c55e; font-weight: bold;">✅ All elements compliant!</p>';
        }

        html += '</div>';
        return html;
    }

    /**
     * Export report as JSON
     */
    exportJSON() {
        return {
            timestamp: new Date().toISOString(),
            url: window.location.href,
            summary: this.getSummary(),
            issues: this.issues.map(issue => ({
                type: issue.type,
                width: issue.width,
                height: issue.height,
                severity: issue.severity,
                selector: issue.selector,
                text: issue.text,
                location: issue.location
            }))
        };
    }

    /**
     * Clear highlights
     */
    clearHighlights() {
        this.issues.forEach(issue => {
            issue.element.style.boxShadow = '';
            issue.element.removeAttribute('title');
        });
    }
};

// Initialize on load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.touchAuditor === 'undefined') {
            window.touchAuditor = new window.TouchTargetAuditor({
                highlightIssues: false
            });
        }
    });
} else {
    if (typeof window.touchAuditor === 'undefined') {
        window.touchAuditor = new window.TouchTargetAuditor({
            highlightIssues: false
        });
    }
}

// Export for testing
if (typeof module !== 'undefined' && module.exports) {
    if (typeof TouchTargetAuditor !== 'undefined') {
        module.exports = TouchTargetAuditor;
    }
}
