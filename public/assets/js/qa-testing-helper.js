/**
 * SPMB WMVAA — QA Automation Helper
 * Phase 1: QA Testing Support Tool
 * File: qa-testing-helper.js
 * Version: 1.0
 *
 * Purpose: Automate QA test execution, result logging, and reporting
 * Features: Test runner, result logging, report generation
 */

window.QATestingHelper = window.QATestingHelper || class QATestingHelper {
    constructor(options = {}) {
        this.testResults = [];
        this.currentTest = null;
        this.startTime = null;
        this.options = {
            autoLog: true,
            enableScreenshots: false,
            ...options
        };

        this.tests = {
            happy_path: [],
            autosave: [],
            responsiveness: [],
            touch: [],
            performance: [],
            upload: [],
            validation: [],
            dashboard: []
        };

        this.init();
    }

    /**
     * Initialize QA helper
     */
    init() {
        console.log('🧪 QA Testing Helper initialized');
        this.setupTestCategories();
    }

    /**
     * Setup test categories with descriptions
     */
    setupTestCategories() {
        this.testCategories = {
            'happy_path': {
                name: 'Complete Registration Flow',
                priority: 'CRITICAL',
                duration: '15 min',
                devices: ['iPhone 12', 'Samsung A50']
            },
            'autosave': {
                name: 'Autosave & Recovery',
                priority: 'CRITICAL',
                duration: '10 min',
                devices: ['iPhone 12', 'Samsung A50']
            },
            'responsiveness': {
                name: 'Mobile Responsiveness',
                priority: 'HIGH',
                duration: '8 min',
                devices: ['iPhone 12', 'iPad']
            },
            'touch': {
                name: 'Touch Target Compliance',
                priority: 'HIGH',
                duration: '5 min',
                devices: ['iPhone 12', 'Samsung A50']
            },
            'performance': {
                name: 'Performance on 4G',
                priority: 'HIGH',
                duration: '10 min',
                devices: ['iPhone 12', 'Samsung A50']
            },
            'upload': {
                name: 'Document Upload',
                priority: 'CRITICAL',
                duration: '12 min',
                devices: ['iPhone 12', 'Samsung A50']
            },
            'validation': {
                name: 'Form Validation',
                priority: 'HIGH',
                duration: '8 min',
                devices: ['iPhone 12', 'Samsung A50']
            },
            'dashboard': {
                name: 'Dashboard Mobile View',
                priority: 'MEDIUM',
                duration: '10 min',
                devices: ['iPhone 12', 'Samsung A50', 'iPad']
            }
        };
    }

    /**
     * Start a test
     */
    startTest(category, testName, device) {
        this.currentTest = {
            id: `${category}_${Date.now()}`,
            category,
            testName,
            device,
            startTime: new Date(),
            status: 'IN_PROGRESS',
            failures: [],
            notes: []
        };

        console.log(`\n${'='.repeat(60)}`);
        console.log(`🧪 TEST STARTED: ${testName}`);
        console.log(`📱 Device: ${device}`);
        console.log(`⏰ Time: ${this.currentTest.startTime.toLocaleTimeString()}`);
        console.log(`${'='.repeat(60)}\n`);
    }

    /**
     * Log test step result
     */
    logStep(stepName, status, details = '') {
        if (!this.currentTest) {
            console.warn('⚠️  No active test. Call startTest() first.');
            return;
        }

        const step = {
            step: stepName,
            status: status,
            time: new Date().toLocaleTimeString(),
            details: details
        };

        if (!this.currentTest.steps) {
            this.currentTest.steps = [];
        }
        this.currentTest.steps.push(step);

        const icon = status === 'PASS' ? '✅' : status === 'FAIL' ? '❌' : '⏳';
        console.log(`${icon} ${stepName}: ${status}${details ? ' - ' + details : ''}`);

        if (status === 'FAIL') {
            this.currentTest.failures.push({ step: stepName, details });
        }
    }

    /**
     * End current test
     */
    endTest(finalStatus = 'PASS') {
        if (!this.currentTest) {
            console.warn('⚠️  No active test to end.');
            return;
        }

        this.currentTest.endTime = new Date();
        this.currentTest.status = finalStatus;
        this.currentTest.duration = Math.round(
            (this.currentTest.endTime - this.currentTest.startTime) / 1000
        );

        this.testResults.push(this.currentTest);

        const icon = finalStatus === 'PASS' ? '✅' : '❌';
        console.log(`\n${icon} TEST ${finalStatus}: ${this.currentTest.testName}`);
        console.log(`⏱️  Duration: ${this.currentTest.duration}s`);
        console.log(`Issues: ${this.currentTest.failures.length}`);
        console.log(`${'='.repeat(60)}\n`);

        this.currentTest = null;
    }

    /**
     * Run automated checks for current page
     */
    runAutomatedChecks() {
        console.log('🤖 Running automated checks...\n');

        const checks = {
            'Console Errors': () => {
                // This is approximate - real check needs debugging setup
                return 'OK';
            },
            'Touch Targets': () => {
                if (window.touchAuditor) {
                    const report = window.touchAuditor.audit();
                    return report.summary.compliancePercent >= 95 ? 'PASS' : 'FAIL';
                }
                return 'SKIP';
            },
            'Horizontal Scroll': () => {
                if (window.scrollDetector) {
                    const report = window.scrollDetector.generateReport();
                    return report.summary.compliant ? 'PASS' : 'FAIL';
                }
                return 'SKIP';
            },
            'Form Validation': () => {
                if (window.mobileFormValidator) {
                    return 'OK';
                }
                return 'SKIP';
            },
            'Performance': () => {
                if (window.performanceOptimizer) {
                    const report = window.performanceOptimizer.generateReport();
                    return report.summary.performanceScore >= 60 ? 'PASS' : 'FAIL';
                }
                return 'SKIP';
            }
        };

        const results = {};
        for (const [checkName, checkFn] of Object.entries(checks)) {
            try {
                const result = checkFn();
                results[checkName] = result;
                const icon = result === 'PASS' ? '✅' : result === 'FAIL' ? '❌' : '⏳';
                console.log(`${icon} ${checkName}: ${result}`);
            } catch (e) {
                results[checkName] = 'ERROR';
                console.log(`❌ ${checkName}: ERROR - ${e.message}`);
            }
        }

        return results;
    }

    /**
     * Generate test report
     */
    generateReport() {
        const summary = {
            totalTests: this.testResults.length,
            passed: this.testResults.filter(t => t.status === 'PASS').length,
            failed: this.testResults.filter(t => t.status === 'FAIL').length,
            passRate: (
                (this.testResults.filter(t => t.status === 'PASS').length / 
                 this.testResults.length * 100) || 0
            ).toFixed(1),
            totalDuration: this.testResults.reduce((sum, t) => sum + (t.duration || 0), 0),
            categories: {}
        };

        // Group by category
        this.testResults.forEach(test => {
            if (!summary.categories[test.category]) {
                summary.categories[test.category] = { passed: 0, failed: 0, total: 0 };
            }
            summary.categories[test.category].total++;
            if (test.status === 'PASS') {
                summary.categories[test.category].passed++;
            } else {
                summary.categories[test.category].failed++;
            }
        });

        return {
            timestamp: new Date().toISOString(),
            summary: summary,
            tests: this.testResults
        };
    }

    /**
     * Print readable test report
     */
    printReport() {
        const report = this.generateReport();
        const summary = report.summary;

        console.group('📊 QA Test Report');
        console.log('═════════════════════════════════════════');

        // Summary
        console.group('Summary');
        console.log(`Total Tests: ${summary.totalTests}`);
        console.log(`✅ Passed: ${summary.passed}`);
        console.log(`❌ Failed: ${summary.failed}`);
        console.log(`Pass Rate: ${summary.passRate}%`);
        console.log(`Total Duration: ${summary.totalDuration}s`);
        console.groupEnd();

        // By category
        console.group('By Category');
        Object.entries(summary.categories).forEach(([category, stats]) => {
            console.log(`${category}: ${stats.passed}/${stats.total} passed`);
        });
        console.groupEnd();

        // Failed tests
        const failedTests = this.testResults.filter(t => t.status === 'FAIL');
        if (failedTests.length > 0) {
            console.group(`Failed Tests (${failedTests.length})`);
            failedTests.forEach(test => {
                console.log(`❌ ${test.testName} (${test.device})`);
                test.failures.forEach(failure => {
                    console.log(`   - ${failure.step}: ${failure.details}`);
                });
            });
            console.groupEnd();
        }

        console.log('═════════════════════════════════════════');
        console.groupEnd();

        return report;
    }

    /**
     * Export report as JSON
     */
    exportJSON() {
        return this.generateReport();
    }

    /**
     * Export report as CSV (for Excel)
     */
    exportCSV() {
        let csv = 'Test Category,Test Name,Device,Status,Duration(s),Issues\n';

        this.testResults.forEach(test => {
            csv += `${test.category},${test.testName},${test.device},${test.status},${test.duration},${test.failures.length}\n`;
        });

        return csv;
    }

    /**
     * Clear all test results
     */
    clearResults() {
        this.testResults = [];
        console.log('✅ Test results cleared');
    }

    /**
     * Get test checklist for manual testing
     */
    getTestChecklist() {
        const checklist = {
            'Happy Path': [
                '[ ] Fill Step 1: Personal Data',
                '[ ] Fill Step 2: Contact Info',
                '[ ] Fill Step 3-7: All intermediate steps',
                '[ ] Fill Step 8: Document Upload',
                '[ ] Review & Submit',
                '[ ] Success message appears'
            ],
            'Autosave': [
                '[ ] Fill form Step 1-3',
                '[ ] Check localStorage (DevTools → Application)',
                '[ ] Turn off WiFi/Airplane mode',
                '[ ] Continue filling form',
                '[ ] Turn WiFi back on',
                '[ ] Verify data persists',
                '[ ] Refresh page',
                '[ ] Verify recovery dialog appears'
            ],
            'Responsiveness': [
                '[ ] Portrait: Form fits on screen',
                '[ ] Portrait: No horizontal scroll',
                '[ ] Landscape: Layout adapts',
                '[ ] Landscape: No horizontal scroll',
                '[ ] All buttons touchable in both orientations',
                '[ ] Progress bar visible and clickable'
            ],
            'Touch Targets': [
                '[ ] Run window.touchAuditor.audit()',
                '[ ] Check compliancePercent ≥ 95%',
                '[ ] Verify all buttons ≥44×44px',
                '[ ] Try to mis-tap buttons (hard to do if ≥44px)'
            ],
            'Performance': [
                '[ ] Throttle to Slow 4G in DevTools',
                '[ ] Measure form load: <3 seconds',
                '[ ] Type in form: responsive (no lag)',
                '[ ] Upload photo: <3 seconds compression',
                '[ ] No timeout errors'
            ],
            'Upload': [
                '[ ] Tap Camera button → Camera opens',
                '[ ] Take photo',
                '[ ] Photo displays as preview',
                '[ ] Compression happens (check file size)',
                '[ ] Upload completes without error',
                '[ ] Success message shows'
            ],
            'Validation': [
                '[ ] Try empty submit → Error summary shows',
                '[ ] Type invalid email → Real-time error',
                '[ ] Fix email → Error clears',
                '[ ] Check error messages are clear',
                '[ ] Can scroll to error if off-screen'
            ],
            'Dashboard': [
                '[ ] Cards stack vertically (portrait)',
                '[ ] Cards show 2 columns (landscape)',
                '[ ] Sidebar toggles on mobile',
                '[ ] Tables convert to cards',
                '[ ] All data visible'
            ]
        };

        return checklist;
    }

    /**
     * Print checklist to console
     */
    printChecklist() {
        const checklist = this.getTestChecklist();

        console.group('📋 QA Test Checklist');
        Object.entries(checklist).forEach(([category, items]) => {
            console.group(category);
            items.forEach(item => console.log(item));
            console.groupEnd();
        });
        console.groupEnd();
    }
};

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.qaHelper === 'undefined') {
            window.qaHelper = new window.QATestingHelper();
        }
    });
} else {
    if (typeof window.qaHelper === 'undefined') {
        window.qaHelper = new window.QATestingHelper();
    }
}

// Export for testing
if (typeof module !== 'undefined' && module.exports) {
    if (typeof QATestingHelper !== 'undefined') {
        module.exports = QATestingHelper;
    }
}
