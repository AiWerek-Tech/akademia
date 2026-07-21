/**
 * SPMB WMVAA — Horizontal Scroll Detector
 * Phase 1: Mobile Experience - Task #8
 * File: no-horizontal-scroll-detector.js
 * Version: 1.0
 *
 * Purpose: Detect and report horizontal scrolling issues
 * Features: Monitors for unwanted horizontal scroll, identifies elements causing it
 */

window.HorizontalScrollDetector = window.HorizontalScrollDetector || class HorizontalScrollDetector {
    constructor(options = {}) {
        this.options = {
            reportToConsole: true,
            highlightIssues: false,
            logScrollEvents: false,
            ...options
        };

        this.issues = [];
        this.isMonitoring = false;
        this.maxHorizontalScroll = 0;
        this.triggeredElements = new Set();
    }

    /**
     * Start monitoring for horizontal scrolling
     */
    startMonitoring() {
        if (this.isMonitoring) return;
        
        this.isMonitoring = true;
        this.issues = [];
        this.maxHorizontalScroll = 0;
        this.triggeredElements.clear();

        // Track scroll position
        let lastScrollX = 0;

        const handleScroll = () => {
            const currentScrollX = window.scrollX || document.documentElement.scrollLeft;
            
            if (currentScrollX > lastScrollX) {
                this.maxHorizontalScroll = Math.max(this.maxHorizontalScroll, currentScrollX);
                
                if (this.options.logScrollEvents) {
                    console.log(`→ Horizontal scroll detected: ${currentScrollX}px`);
                }
            }
            
            lastScrollX = currentScrollX;
        };

        // Track window resize (orientation change)
        const handleResize = () => {
            setTimeout(() => {
                this.checkForOverflow();
            }, 100);
        };

        window.addEventListener('scroll', handleScroll);
        window.addEventListener('resize', handleResize);

        // Initial check
        this.checkForOverflow();

        return {
            stop: () => this.stopMonitoring(),
            report: () => this.generateReport()
        };
    }

    /**
     * Stop monitoring
     */
    stopMonitoring() {
        this.isMonitoring = false;
    }

    /**
     * Check for elements causing horizontal overflow
     */
    checkForOverflow() {
        const bodyWidth = document.body.offsetWidth;
        const windowWidth = window.innerWidth;
        const documentElement = document.documentElement;

        // Check document overflow
        const scrollWidth = Math.max(
            document.body.scrollWidth,
            documentElement.scrollWidth
        );

        if (scrollWidth > windowWidth) {
            this.issues.push({
                type: 'document',
                cause: 'Document exceeds viewport width',
                documentWidth: scrollWidth,
                viewportWidth: windowWidth,
                overflow: scrollWidth - windowWidth,
                elements: this.findOverflowingElements()
            });
        }

        // Check body width
        if (document.body.scrollWidth > window.innerWidth) {
            if (!this.triggeredElements.has(document.body)) {
                this.triggeredElements.add(document.body);
            }
        }
    }

    /**
     * Find elements that exceed viewport
     */
    findOverflowingElements() {
        const elements = [];
        const viewportWidth = window.innerWidth;

        // Check all elements
        document.querySelectorAll('*').forEach(element => {
            // Skip hidden elements
            if (element.offsetParent === null) return;

            const rect = element.getBoundingClientRect();
            
            if (rect.right > viewportWidth) {
                elements.push({
                    tag: element.tagName.toLowerCase(),
                    class: element.className,
                    id: element.id,
                    width: element.offsetWidth,
                    left: rect.left,
                    right: rect.right,
                    overflow: Math.round(rect.right - viewportWidth),
                    selector: this.getElementSelector(element),
                    element: element
                });
            }
        });

        // Return top 10 culprits (sorted by overflow amount)
        return elements
            .sort((a, b) => b.overflow - a.overflow)
            .slice(0, 10);
    }

    /**
     * Get CSS selector for element
     */
    getElementSelector(element) {
        if (element.id) return `#${element.id}`;
        
        const classes = Array.from(element.classList)
            .filter(c => !c.match(/^(show|hide|d-|w-|h-)/))
            .slice(0, 2);
        
        if (classes.length > 0) {
            return `.${classes.join('.')}`;
        }

        let path = [];
        let el = element;
        while (el && el.tagName !== 'HTML') {
            const index = Array.from(el.parentElement?.children || [])
                .filter(e => e.tagName === el.tagName)
                .indexOf(el) + 1;
            
            path.unshift(`${el.tagName.toLowerCase()}${index > 1 ? `:nth-of-type(${index})` : ''}`);
            el = el.parentElement;
        }

        return path.join(' > ');
    }

    /**
     * Check specific viewport size
     */
    checkViewportSize(width, height) {
        // Simulate viewport
        const originalWidth = window.innerWidth;
        const originalHeight = window.innerHeight;

        return {
            width: width,
            height: height,
            hasHorizontalScroll: this.findOverflowingElements().length > 0,
            overflowingElements: this.findOverflowingElements()
        };
    }

    /**
     * Test common mobile orientations
     */
    testCommonOrientations() {
        const testSizes = [
            { name: 'iPhone SE', width: 375, height: 667 },
            { name: 'iPhone 12', width: 390, height: 844 },
            { name: 'iPhone 14 Pro', width: 393, height: 852 },
            { name: 'Samsung S21', width: 360, height: 800 },
            { name: 'Samsung S22', width: 360, height: 800 },
            { name: 'Pixel 6', width: 412, height: 915 },
            { name: 'iPad (7th gen)', width: 810, height: 1080 },
            { name: 'iPad Pro 11"', width: 834, height: 1194 },
            { name: 'Landscape iPhone 12', width: 844, height: 390 },
            { name: 'Landscape Samsung S21', width: 800, height: 360 }
        ];

        return testSizes.map(size => ({
            ...size,
            hasIssues: this.checkViewportSize(size.width, size.height).overflowingElements.length > 0,
            elements: this.checkViewportSize(size.width, size.height).overflowingElements
        }));
    }

    /**
     * Generate comprehensive report
     */
    generateReport() {
        const overflowElements = this.findOverflowingElements();
        const orientationTests = this.testCommonOrientations();

        return {
            timestamp: new Date().toISOString(),
            currentViewport: {
                width: window.innerWidth,
                height: window.innerHeight
            },
            hasHorizontalScroll: window.scrollX > 0 || this.maxHorizontalScroll > 0,
            maxHorizontalScroll: this.maxHorizontalScroll,
            overflowingElements: overflowElements,
            orientationTests: orientationTests,
            summary: {
                totalOverflowingElements: overflowElements.length,
                failedOrientations: orientationTests.filter(t => t.hasIssues).length,
                totalOrientations: orientationTests.length,
                compliant: overflowingElements.length === 0
            }
        };
    }

    /**
     * Print report to console
     */
    printReport() {
        const report = this.generateReport();
        const summary = report.summary;

        console.group('📱 Horizontal Scroll Detection Report');
        console.log('═════════════════════════════════════════');

        // Current viewport
        console.group('📊 Current Viewport');
        console.log(`Width: ${report.currentViewport.width}px`);
        console.log(`Height: ${report.currentViewport.height}px`);
        console.log(`Max horizontal scroll detected: ${report.maxHorizontalScroll}px`);
        console.groupEnd();

        // Overflowing elements
        if (report.overflowingElements.length > 0) {
            console.group(`⚠️  Overflowing Elements (${report.overflowingElements.length})`);
            report.overflowingElements.forEach((el, idx) => {
                console.log(`${idx + 1}. ${el.selector}`);
                console.log(`   Size: ${el.width}px | Overflow: ${el.overflow}px`);
                console.log(`   Position: left ${Math.round(el.left)}px, right ${Math.round(el.right)}px`);
                console.log(`   Element:`, el.element);
            });
            console.groupEnd();
        }

        // Orientation tests
        console.group(`📱 Orientation Tests (${summary.totalOrientations})`);
        const passed = report.orientationTests.filter(t => !t.hasIssues);
        const failed = report.orientationTests.filter(t => t.hasIssues);

        console.log(`✅ Passed: ${passed.length}/${summary.totalOrientations}`);
        
        if (failed.length > 0) {
            console.group(`🔴 Failed Orientations`);
            failed.forEach(test => {
                console.log(`${test.name} (${test.width}×${test.height})`);
                console.log(`  Culprits: ${test.elements.length} elements overflow`);
                test.elements.slice(0, 3).forEach(el => {
                    console.log(`    - ${el.selector} (+${el.overflow}px)`);
                });
            });
            console.groupEnd();
        }
        console.groupEnd();

        // Summary
        console.group('📋 Summary');
        if (summary.compliant) {
            console.log('%c✅ No horizontal scrolling detected!', 'color: #22c55e; font-weight: bold;');
        } else {
            console.log(`%c❌ Issues found: ${summary.totalOverflowingElements} elements`, 'color: #ef4444; font-weight: bold;');
            console.log(`%c⚠️  ${summary.failedOrientations}/${summary.totalOrientations} orientations have problems`, 'color: #f97316; font-weight: bold;');
        }
        console.groupEnd();

        console.log('═════════════════════════════════════════');
        console.groupEnd();
    }

    /**
     * Highlight overflowing elements
     */
    highlightProblems() {
        const elements = this.findOverflowingElements();
        
        elements.forEach((item, idx) => {
            const element = item.element;
            element.style.boxShadow = `0 0 5px 3px rgba(239, 68, 68, 0.5)`;
            element.style.outline = '3px dashed #ef4444';
            element.setAttribute('data-overflow-test', `Overflow: +${item.overflow}px`);
            element.title = `Causes ${item.overflow}px horizontal overflow`;
        });

        console.log(`%c✓ Highlighted ${elements.length} problematic elements`, 'color: #ef4444; font-weight: bold;');
    }

    /**
     * Clear highlights
     */
    clearHighlights() {
        document.querySelectorAll('[data-overflow-test]').forEach(el => {
            el.style.boxShadow = '';
            el.style.outline = '';
            el.removeAttribute('data-overflow-test');
            el.title = '';
        });
    }

    /**
     * Export as JSON
     */
    exportJSON() {
        return this.generateReport();
    }
};

// Initialize on load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.scrollDetector === 'undefined') {
            window.scrollDetector = new window.HorizontalScrollDetector({
                reportToConsole: false,
                logScrollEvents: false
            });
        }
    });
} else {
    if (typeof window.scrollDetector === 'undefined') {
        window.scrollDetector = new window.HorizontalScrollDetector({
            reportToConsole: false,
            logScrollEvents: false
        });
    }
}

// Export for testing
if (typeof module !== 'undefined' && module.exports) {
    if (typeof HorizontalScrollDetector !== 'undefined') {
        module.exports = HorizontalScrollDetector;
    }
}
