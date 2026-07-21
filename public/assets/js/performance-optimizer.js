/**
 * SPMB WMVAA — Performance Optimizer
 * Phase 1: Mobile Experience - Task #9
 * File: performance-optimizer.js
 * Version: 1.0
 *
 * Purpose: Audit, report, and optimize application performance
 * Features: Bundle analysis, LCP tracking, CLS detection, performance metrics
 */

window.PerformanceOptimizer = window.PerformanceOptimizer || class PerformanceOptimizer {
    constructor(options = {}) {
        this.options = {
            reportToConsole: true,
            trackAllMetrics: true,
            enableFieldData: true,
            ...options
        };

        this.metrics = {
            core: {
                LCP: null,  // Largest Contentful Paint
                FID: null,  // First Input Delay
                CLS: null   // Cumulative Layout Shift
            },
            performance: {},
            resources: [],
            navigation: null
        };

        this.initialized = false;
    }

    /**
     * Initialize performance monitoring
     */
    initialize() {
        if (this.initialized) return;
        this.initialized = true;

        // Track Core Web Vitals
        this.trackCoreWebVitals();

        // Track page load
        this.trackPageLoad();

        // Track resources
        this.trackResources();

        return this;
    }

    /**
     * Track Core Web Vitals using Web Vitals library or native APIs
     */
    trackCoreWebVitals() {
        // LCP - Largest Contentful Paint
        try {
            const observerLCP = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                const lastEntry = entries[entries.length - 1];
                this.metrics.core.LCP = {
                    value: lastEntry.renderTime || lastEntry.loadTime,
                    element: lastEntry.element,
                    time: new Date().toISOString()
                };
            });

            observerLCP.observe({ entryTypes: ['largest-contentful-paint'] });
        } catch (e) {
            console.warn('LCP tracking not available', e);
        }

        // FID - First Input Delay (deprecated, replaced by INP, but still track)
        try {
            const observerFID = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    this.metrics.core.FID = {
                        value: entry.processingDuration,
                        time: new Date().toISOString()
                    };
                }
            });

            observerFID.observe({ entryTypes: ['first-input'] });
        } catch (e) {
            console.warn('FID tracking not available', e);
        }

        // CLS - Cumulative Layout Shift
        try {
            let clsValue = 0;
            const observerCLS = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (!entry.hadRecentInput) {
                        clsValue += entry.value;
                        this.metrics.core.CLS = {
                            value: clsValue,
                            time: new Date().toISOString()
                        };
                    }
                }
            });

            observerCLS.observe({ entryTypes: ['layout-shift'] });
        } catch (e) {
            console.warn('CLS tracking not available', e);
        }
    }

    /**
     * Track page load metrics
     */
    trackPageLoad() {
        // Wait for page to load
        if (document.readyState === 'loading') {
            window.addEventListener('load', () => {
                this.capturePageMetrics();
            });
        } else {
            this.capturePageMetrics();
        }
    }

    /**
     * Capture page metrics after load
     */
    capturePageMetrics() {
        setTimeout(() => {
            const navTiming = performance.getEntriesByType('navigation')[0];
            if (navTiming) {
                this.metrics.navigation = {
                    domContentLoaded: navTiming.domContentLoadedEventEnd - navTiming.domContentLoadedEventStart,
                    loadComplete: navTiming.loadEventEnd - navTiming.loadEventStart,
                    domInteractive: navTiming.domInteractive - navTiming.fetchStart,
                    responseEnd: navTiming.responseEnd - navTiming.fetchStart,
                    ttfb: navTiming.responseStart - navTiming.fetchStart
                };
            }

            // Calculate other metrics
            const perfData = performance.timing;
            this.metrics.performance = {
                pageLoadTime: perfData.loadEventEnd - perfData.navigationStart,
                connectTime: perfData.responseEnd - perfData.requestStart,
                renderTime: perfData.domComplete - perfData.domLoading,
                domReadyTime: perfData.domContentLoadedEventEnd - perfData.navigationStart,
                resourceDownloadTime: perfData.responseEnd - perfData.fetchStart
            };
        }, 0);
    }

    /**
     * Track resource loading
     */
    trackResources() {
        const observerResource = new PerformanceObserver((list) => {
            const entries = list.getEntries();
            entries.forEach(entry => {
                this.metrics.resources.push({
                    name: entry.name,
                    type: entry.initiatorType,
                    duration: entry.duration,
                    size: entry.transferSize || 0,
                    cached: entry.transferSize === 0 && entry.decodedBodySize > 0
                });
            });
        });

        try {
            observerResource.observe({ entryTypes: ['resource'] });
        } catch (e) {
            console.warn('Resource tracking not available', e);
        }
    }

    /**
     * Get bundle analysis
     */
    getBundleAnalysis() {
        const scripts = Array.from(document.querySelectorAll('script[src]'));
        const stylesheets = Array.from(document.querySelectorAll('link[rel="stylesheet"]'));
        const images = Array.from(document.querySelectorAll('img[src]'));

        const scriptSize = scripts.reduce((sum, s) => {
            const resource = this.metrics.resources.find(r => r.name.includes(s.src.split('/').pop()));
            return sum + (resource ? resource.size : 0);
        }, 0);

        const cssSize = stylesheets.reduce((sum, s) => {
            const resource = this.metrics.resources.find(r => r.name.includes(s.href.split('/').pop()));
            return sum + (resource ? resource.size : 0);
        }, 0);

        return {
            scripts: {
                count: scripts.length,
                totalSize: scriptSize,
                avgSize: scriptSize / scripts.length,
                list: scripts.map(s => ({
                    src: s.src.split('/').pop(),
                    async: s.async,
                    defer: s.defer
                }))
            },
            stylesheets: {
                count: stylesheets.length,
                totalSize: cssSize,
                avgSize: cssSize / stylesheets.length,
                list: stylesheets.map(s => ({
                    href: s.href.split('/').pop(),
                    media: s.media || 'all'
                }))
            },
            images: {
                count: images.length,
                list: images.slice(0, 10).map(img => ({
                    src: img.src.split('/').pop(),
                    width: img.width,
                    height: img.height,
                    naturalWidth: img.naturalWidth,
                    naturalHeight: img.naturalHeight
                }))
            }
        };
    }

    /**
     * Analyze JavaScript execution
     */
    analyzeJSExecution() {
        const longTasks = performance.getEntriesByType('longtask') || [];
        const userTiming = performance.getEntriesByType('measure') || [];

        return {
            longTaskCount: longTasks.length,
            longTasks: longTasks.slice(0, 10).map(t => ({
                duration: t.duration,
                startTime: t.startTime
            })),
            customMetrics: userTiming.map(m => ({
                name: m.name,
                duration: m.duration
            }))
        };
    }

    /**
     * Check image optimization
     */
    checkImageOptimization() {
        const images = Array.from(document.querySelectorAll('img'));
        const issues = [];

        images.forEach(img => {
            // Check 1: Size exceeds viewport
            if (img.naturalWidth > window.innerWidth * 2) {
                issues.push({
                    type: 'oversized',
                    src: img.src.split('/').pop(),
                    naturalWidth: img.naturalWidth,
                    viewport: window.innerWidth,
                    message: `Image ${img.naturalWidth}px wide, viewport ${window.innerWidth}px`
                });
            }

            // Check 2: Missing width/height (causes CLS)
            if (!img.hasAttribute('width') && !img.hasAttribute('height')) {
                issues.push({
                    type: 'missing_dimensions',
                    src: img.src.split('/').pop(),
                    message: 'Missing width/height attributes (causes layout shift)'
                });
            }

            // Check 3: Large file format
            if (img.src.endsWith('.png') || img.src.endsWith('.bmp')) {
                issues.push({
                    type: 'suboptimal_format',
                    src: img.src.split('/').pop(),
                    current: img.src.split('.').pop().toUpperCase(),
                    suggested: 'WebP or JPEG',
                    message: `Use ${img.src.endsWith('.png') ? 'WebP' : 'JPEG'} instead of ${img.src.split('.').pop().toUpperCase()}`
                });
            }
        });

        return {
            totalImages: images.length,
            optimizationIssues: issues.length,
            issues: issues
        };
    }

    /**
     * Check CSS optimization
     */
    checkCSSOptimization() {
        const stylesheets = Array.from(document.querySelectorAll('link[rel="stylesheet"]'));
        const issues = [];

        stylesheets.forEach(link => {
            // Check 1: Render-blocking CSS
            if (link.media === 'all' || !link.media) {
                // Check if it's blocking rendering
                if (link.rel === 'stylesheet') {
                    issues.push({
                        type: 'render_blocking',
                        href: link.href.split('/').pop(),
                        message: 'Stylesheet may block rendering'
                    });
                }
            }

            // Check 2: Multiple CSS files
        });

        // Check 3: Inline styles
        const inlineStyles = document.querySelectorAll('[style]');
        if (inlineStyles.length > 20) {
            issues.push({
                type: 'excessive_inline_styles',
                count: inlineStyles.length,
                message: `${inlineStyles.length} elements have inline styles (use CSS classes instead)`
            });
        }

        return {
            totalStylesheets: stylesheets.length,
            optimizationIssues: issues.length,
            issues: issues
        };
    }

    /**
     * Generate comprehensive report
     */
    generateReport() {
        const bundle = this.getBundleAnalysis();
        const jsExec = this.analyzeJSExecution();
        const images = this.checkImageOptimization();
        const css = this.checkCSSOptimization();

        return {
            timestamp: new Date().toISOString(),
            url: window.location.href,
            coreWebVitals: this.metrics.core,
            pageMetrics: this.metrics.performance,
            navigationMetrics: this.metrics.navigation,
            bundle: bundle,
            jsExecution: jsExec,
            images: images,
            css: css,
            summary: this.calculateSummary(bundle, images, css)
        };
    }

    /**
     * Calculate summary scores
     */
    calculateSummary(bundle, images, css) {
        const scriptSize = bundle.scripts.totalSize / 1024;  // KB
        const cssSize = bundle.stylesheets.totalSize / 1024;  // KB
        const totalAssets = bundle.scripts.count + bundle.stylesheets.count;

        let score = 100;

        // Deduct for large bundles
        if (scriptSize > 200) score -= 10;
        if (scriptSize > 500) score -= 20;
        if (cssSize > 100) score -= 5;
        if (cssSize > 200) score -= 10;

        // Deduct for optimization issues
        score -= Math.min(images.optimizationIssues * 2, 20);
        score -= Math.min(css.optimizationIssues * 3, 15);

        return {
            performanceScore: Math.max(0, score),
            scriptBundleSize: `${scriptSize.toFixed(1)} KB`,
            cssBundleSize: `${cssSize.toFixed(1)} KB`,
            totalAssets: totalAssets,
            optimization: score >= 80 ? 'Good' : score >= 60 ? 'Fair' : 'Needs Work'
        };
    }

    /**
     * Print report to console
     */
    printReport() {
        const report = this.generateReport();
        const summary = report.summary;

        console.group('⚡ Performance Optimization Report');
        console.log('═════════════════════════════════════════');

        // Core Web Vitals
        console.group('📊 Core Web Vitals');
        const lcpValue = report.coreWebVitals.LCP ? report.coreWebVitals.LCP.value.toFixed(0) : 'N/A';
        const lcpStatus = (report.coreWebVitals.LCP && report.coreWebVitals.LCP.value > 2500) ? ' ⚠️ (target: <2.5s)' : ' ✓';
        console.log('LCP: ' + lcpValue + 'ms' + lcpStatus);

        const clsValue = report.coreWebVitals.CLS ? report.coreWebVitals.CLS.value.toFixed(2) : 'N/A';
        const clsStatus = (report.coreWebVitals.CLS && report.coreWebVitals.CLS.value > 0.1) ? ' ⚠️ (target: <0.1)' : ' ✓';
        console.log('CLS: ' + clsValue + clsStatus);
        console.groupEnd();

        // Page Metrics
        if (report.pageMetrics) {
            console.group('⏱️  Page Load Metrics');
            console.log(`Total Load Time: ${report.pageMetrics.pageLoadTime.toFixed(0)}ms`);
            console.log(`DOM Ready: ${report.pageMetrics.domReadyTime.toFixed(0)}ms`);
            console.log(`Render Time: ${report.pageMetrics.renderTime.toFixed(0)}ms`);
            console.groupEnd();
        }

        // Bundle Analysis
        console.group(`📦 Bundle Analysis`);
        console.log(`Scripts: ${report.bundle.scripts.count} files, ${summary.scriptBundleSize}`);
        console.log(`Stylesheets: ${report.bundle.stylesheets.count} files, ${summary.cssBundleSize}`);
        console.log(`Images: ${report.bundle.images.count} images`);
        console.groupEnd();

        // Image Optimization
        if (report.images.optimizationIssues > 0) {
            console.group(`🖼️  Image Optimization (${report.images.optimizationIssues} issues)`);
            report.images.issues.slice(0, 5).forEach(issue => {
                console.log(`  ${issue.type}: ${issue.message}`);
            });
            console.groupEnd();
        }

        // CSS Optimization
        if (report.css.optimizationIssues > 0) {
            console.group(`🎨 CSS Optimization (${report.css.optimizationIssues} issues)`);
            report.css.issues.slice(0, 5).forEach(issue => {
                console.log(`  ${issue.type}: ${issue.message}`);
            });
            console.groupEnd();
        }

        // Summary Score
        console.group('📈 Performance Score');
        console.log(`%cScore: ${summary.performanceScore}/100`, `color: ${summary.performanceScore >= 80 ? '#22c55e' : summary.performanceScore >= 60 ? '#f97316' : '#ef4444'}; font-weight: bold;`);
        console.log(`Status: ${summary.optimization}`);
        console.groupEnd();

        console.log('═════════════════════════════════════════');
        console.groupEnd();
    }

    /**
     * Export as JSON
     */
    exportJSON() {
        return this.generateReport();
    }

    /**
     * Get optimization recommendations
     */
getRecommendations() {
        const report = this.generateReport();
        const recommendations = [];

        // Script bundle recommendations
        if (report.bundle.scripts.totalSize / 1024 > 200) {
            recommendations.push({
                priority: 'high',
                category: 'JavaScript',
                issue: 'Large script bundle (>200 KB)',
                action: 'Minify and enable compression',
                impact: 'Reduce JS by 40-60%'
            });
        }

        // Image recommendations
        if (report.images.optimizationIssues > 0) {
            recommendations.push({
                priority: 'high',
                category: 'Images',
                issue: `${report.images.optimizationIssues} image optimization issues`,
                action: 'Convert to WebP, resize, optimize',
                impact: 'Reduce image size by 30-50%'
            });
        }

        // CSS recommendations
        if (report.css.optimizationIssues > 0) {
            recommendations.push({
                priority: 'medium',
                category: 'CSS',
                issue: `${report.css.optimizationIssues} CSS optimization issues`,
                action: 'Remove unused styles, inline critical CSS',
                impact: 'Reduce CSS by 20-40%'
            });
        }

        // Core Web Vitals recommendations
        if (report.coreWebVitals.LCP && report.coreWebVitals.LCP.value > 2500) {
            recommendations.push({
                priority: 'high',
                category: 'LCP',
                issue: `Largest Contentful Paint: ${report.coreWebVitals.LCP.value.toFixed(0)}ms`,
                action: 'Optimize LCP element (image, text, etc)',
                impact: 'Improve LCP by 500-1000ms'
            });
        }

        if (report.coreWebVitals.CLS && report.coreWebVitals.CLS.value > 0.1) {
            recommendations.push({
                priority: 'medium',
                category: 'CLS',
                issue: `Cumulative Layout Shift: ${report.coreWebVitals.CLS.value.toFixed(2)}`,
                action: 'Add width/height to images, reserve space for ads',
                impact: 'Reduce CLS by 50%'
            });
        }

        return recommendations;
    }
};

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.performanceOptimizer === 'undefined') {
            window.performanceOptimizer = new window.PerformanceOptimizer();
            window.performanceOptimizer.initialize();
        }
    });
} else {
    if (typeof window.performanceOptimizer === 'undefined') {
        window.performanceOptimizer = new window.PerformanceOptimizer();
        window.performanceOptimizer.initialize();
    }
}

// Export for testing
if (typeof module !== 'undefined' && module.exports) {
    if (typeof PerformanceOptimizer !== 'undefined') {
        module.exports = PerformanceOptimizer;
    }
}
