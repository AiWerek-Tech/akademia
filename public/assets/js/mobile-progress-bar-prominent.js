/**
 * SPMB WMVAA — Mobile Progress Bar Prominent Enhancement
 * Phase 1: Mobile Experience - Task #2
 * Version: 1.1
 * 
 * Purpose: Enhance progress bar with animations, transitions, and prominent styling
 * Dependencies: wizard-draft-manager.js
 */

class ProminentProgressBar {
    constructor() {
        this.progressBar = document.getElementById('mobileProgressBar');
        this.currentStep = 1;
        this.totalSteps = 9;
        this.lastStep = 1;
        
        if (!this.progressBar) {
            console.warn('Progress bar element not found');
            return;
        }
        
        this.init();
    }

    /**
     * Initialize progress bar on page load
     */
    init() {
        this.attachEventListeners();
        this.renderInitialState();
    }

    updateProgressBar(step) {
        this.currentStep = step;
        const percentage = (step / this.totalSteps) * 100;
        
        // Update text elements
        const stepSpan = document.getElementById('currentStep');
        const percentSpan = document.getElementById('progressPercent');
        
        if (stepSpan) {
            stepSpan.textContent = step;
            stepSpan.style.animation = 'none';
            setTimeout(() => {
                stepSpan.style.animation = 'fadeInScale 0.4s ease-out';
            }, 10);
        }
        
        if (percentSpan) {
            percentSpan.textContent = Math.round(percentage);
        }
        
        // Update progress fill bar
        const progressFill = document.getElementById('progressFill');
        if (progressFill) {
            // Trigger animation
            progressFill.style.transition = 'none';
            progressFill.style.width = percentage + '%';
            
            // Re-enable transition after initial update
            setTimeout(() => {
                progressFill.style.transition = 'width 0.5s cubic-bezier(0.34, 1.56, 0.64, 1)';
            }, 50);
        }
        
        // Trigger transition celebration effect
        if (step > this.lastStep) {
            this.triggerStepTransitionAnimation();
            this.lastStep = step;
        }
    }

    /**
     * Trigger animation when user advances to next step
     */
    triggerStepTransitionAnimation() {
        if (!this.progressBar) return;
        
        // Add animation class
        this.progressBar.classList.add('step-transition-active');
        
        // Remove class after animation completes
        setTimeout(() => {
            this.progressBar.classList.remove('step-transition-active');
        }, 600);
    }

    /**
     * Update progress status message
     */
    updateStatusMessage(message = null) {
        if (!this.progressBar) {
            return;
        }

        let existingMessage = this.progressBar.querySelector('.progress-status-message');
        
        if (!message) {
            // Auto-generate message based on progress
            const percentage = (this.currentStep / this.totalSteps) * 100;
            
            if (percentage === 100) {
                message = '✓ Selesaikan untuk submit';
            } else if (percentage >= 75) {
                message = '⚡ Hampir selesai!';
            } else if (percentage >= 50) {
                message = '↪ Lanjutkan pengisian...';
            } else {
                message = '↻ Mulai pengisian data';
            }
        }
        
        if (existingMessage) {
            existingMessage.innerHTML = `<i data-lucide="info"></i>${message}`;
        } else {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'progress-status-message';
            messageDiv.innerHTML = `<i data-lucide="info"></i>${message}`;
            this.progressBar.appendChild(messageDiv);
        }
        
        // Reinitialize Lucide icons if available
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    /**
     * Render initial state on page load
     */
    renderInitialState() {
        if (!this.progressBar) {
            return;
        }

        const currentStepFromPage = document.querySelector('[data-current-step]');
        if (currentStepFromPage) {
            this.currentStep = parseInt(currentStepFromPage.dataset.currentStep) || 1;
        }
        
        this.updateProgressBar(this.currentStep);
        this.updateStatusMessage();
    }

    /**
     * Setup observer for step changes via mutation observer
     */
    setupStepObserver() {
        const stepContainer = document.querySelector('.step-container');
        if (!stepContainer) return;
        
        const observer = new MutationObserver(() => {
            this.detectStepChange();
        });
        
        observer.observe(stepContainer, {
            attributes: true,
            subtree: true,
            attributeFilter: ['class', 'data-step']
        });
    }

    /**
     * Detect if current step has changed
     */
    detectStepChange() {
        // Check for active step in step items
        const activeStep = document.querySelector('.step-item.active');
        if (activeStep) {
            const stepItems = document.querySelectorAll('.step-item');
            let stepNumber = 1;
            
            stepItems.forEach((item, index) => {
                if (item.classList.contains('active')) {
                    stepNumber = index + 1;
                }
            });
            
            if (stepNumber !== this.currentStep) {
                this.updateProgressBar(stepNumber);
                this.updateStatusMessage();
            }
        }
    }

    /**
     * Get progress percentage
     */
    getProgress() {
        return (this.currentStep / this.totalSteps) * 100;
    }

    /**
     * Check if form is complete
     */
    isComplete() {
        return this.currentStep === this.totalSteps;
    }

    /**
     * Get visual progress bar state
     */
    getState() {
        return {
            currentStep: this.currentStep,
            totalSteps: this.totalSteps,
            percentage: this.getProgress(),
            isComplete: this.isComplete()
        };
    }

    /**
     * Attach event listeners for step navigation
     */
    attachEventListeners() {
        // Listen for next button clicks
        const nextBtn = document.getElementById('nextBtn');
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                setTimeout(() => {
                    this.detectStepChange();
                }, 100);
            });
        }
        
        // Listen for previous button clicks
        const prevBtn = document.getElementById('prevBtn');
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                setTimeout(() => {
                    this.detectStepChange();
                }, 100);
            });
        }
    }

    /**
     * Animate progress to a specific step (for testing/transitions)
     */
    animateTo(targetStep) {
        if (targetStep < 1 || targetStep > this.totalSteps) return;
        
        const steps = targetStep - this.currentStep;
        let currentAnimStep = this.currentStep;
        
        const interval = setInterval(() => {
            if (steps > 0) {
                currentAnimStep++;
                if (currentAnimStep > targetStep) {
                    clearInterval(interval);
                    return;
                }
            } else {
                currentAnimStep--;
                if (currentAnimStep < targetStep) {
                    clearInterval(interval);
                    return;
                }
            }
            
            this.updateProgressBar(currentAnimStep);
        }, 150);
    }

    /**
     * Reset progress bar to step 1
     */
    reset() {
        this.currentStep = 1;
        this.lastStep = 1;
        this.updateProgressBar(1);
        this.updateStatusMessage();
    }

    /**
     * Enable/disable progress bar interaction
     */
    setEnabled(enabled = true) {
        if (!this.progressBar) return;
        this.progressBar.style.opacity = enabled ? '1' : '0.5';
        this.progressBar.style.pointerEvents = enabled ? 'auto' : 'none';
    }
}

// ─────────────────────────────────────────────────────────────
// INITIALIZATION & AUTO-START
// ─────────────────────────────────────────────────────────────

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.progressBar = new ProminentProgressBar();
    });
} else {
    window.progressBar = new ProminentProgressBar();
}

/**
 * Extend existing WizardDraftManager to integrate with progress bar
 * This allows draft manager to update progress bar on step changes
 */
if (typeof WizardDraftManager !== 'undefined') {
    const originalUpdateProgressBar = WizardDraftManager.prototype.updateProgressBar;
    
    WizardDraftManager.prototype.updateProgressBar = function(step) {
        // Call original function if exists
        if (originalUpdateProgressBar) {
            originalUpdateProgressBar.call(this, step);
        }
        
        // Update prominent progress bar
        if (window.progressBar) {
            const currentStep = step || this.currentStep;
            window.progressBar.updateProgressBar(currentStep);
            window.progressBar.updateStatusMessage();
        }
    };
}

// Export for testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ProminentProgressBar;
}
