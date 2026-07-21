/**
 * SPMB WMVAA — Mobile Form Validation Polish
 * Phase 1: Mobile Experience - Task #7
 * File: mobile-form-validator.js
 * Version: 1.0
 *
 * Purpose: Real-time validation with enhanced mobile UX
 * Features: Field validation, error messages, success feedback
 */

class MobileFormValidator {
    constructor(formSelector = 'form', options = {}) {
        this.form = document.querySelector(formSelector);
        this.options = {
            validateOnBlur: true,
            validateOnInput: true,
            showSuccessMessage: false,
            debounceDelay: 300,
            ...options
        };

        this.validationRules = new Map();
        this.fieldStates = new Map();
        this.debounceTimers = new Map();

        if (this.form) {
            this.init();
        }
    }

    /**
     * Initialize validator
     */
    init() {
        this.setupEventListeners();
        this.registerDefaultRules();
    }

    /**
     * Setup event listeners on form fields
     */
    setupEventListeners() {
        const inputs = this.form.querySelectorAll(
            'input[type="text"], input[type="email"], input[type="tel"], input[type="number"], ' +
            'input[type="date"], input[type="password"], select, textarea'
        );

        inputs.forEach(field => {
            // Blur validation (always reliable)
            if (this.options.validateOnBlur) {
                field.addEventListener('blur', (e) => this.handleFieldBlur(e));
            }

            // Input validation (debounced for performance)
            if (this.options.validateOnInput) {
                field.addEventListener('input', (e) => this.handleFieldInput(e));
            }

            // Character counter for textareas
            if (field.tagName === 'TEXTAREA' && field.maxLength) {
                field.addEventListener('input', () => this.updateCharCounter(field));
            }
        });

        // Form submit validation
        this.form.addEventListener('submit', (e) => this.handleFormSubmit(e));
    }

    /**
     * Handle field blur event (always validate)
     */
    handleFieldBlur(event) {
        const field = event.target;
        clearTimeout(this.debounceTimers.get(field.name));
        this.validateField(field);
    }

    /**
     * Handle field input event (debounced)
     */
    handleFieldInput(event) {
        const field = event.target;

        // Clear previous timer
        clearTimeout(this.debounceTimers.get(field.name));

        // Set new debounced timer
        const timer = setTimeout(() => {
            this.validateField(field);
        }, this.options.debounceDelay);

        this.debounceTimers.set(field.name, timer);
    }

    /**
     * Validate single field
     */
    validateField(field) {
        const fieldName = field.name || field.id;
        const rules = this.validationRules.get(fieldName) || [];
        const errors = [];

        // Run all validation rules
        for (const rule of rules) {
            const error = rule.validate(field.value, field);
            if (error) {
                errors.push(error);
            }
        }

        // Update field state
        const isValid = errors.length === 0;
        this.updateFieldState(field, isValid, errors);

        return isValid;
    }

    /**
     * Update field visual state
     */
    updateFieldState(field, isValid, errors) {
        const fieldName = field.name || field.id;

        // Remove previous state
        field.classList.remove('is-valid', 'is-invalid');

        // Add new state
        if (isValid) {
            field.classList.add('is-valid');
            this.showFieldSuccess(field);
        } else {
            field.classList.add('is-invalid');
            this.showFieldErrors(field, errors);
        }

        // Store state
        this.fieldStates.set(fieldName, { isValid, errors });
    }

    /**
     * Show success feedback
     */
    showFieldSuccess(field) {
        const container = this.getFieldContainer(field);
        const validFeedback = container.querySelector('.valid-feedback');

        if (validFeedback) {
            validFeedback.classList.add('show');
        }

        // Hide error feedback
        const invalidFeedback = container.querySelector('.invalid-feedback');
        if (invalidFeedback) {
            invalidFeedback.classList.remove('show');
        }

        if (this.options.showSuccessMessage) {
            this.showToast(`✓ ${field.getAttribute('aria-label') || field.name || 'Kolom'} valid`, 'success');
        }
    }

    /**
     * Show error messages
     */
    showFieldErrors(field, errors) {
        const container = this.getFieldContainer(field);
        let invalidFeedback = container.querySelector('.invalid-feedback');

        // Hide success feedback
        const validFeedback = container.querySelector('.valid-feedback');
        if (validFeedback) {
            validFeedback.classList.remove('show');
        }

        if (!invalidFeedback) {
            invalidFeedback = document.createElement('div');
            invalidFeedback.className = 'invalid-feedback d-block';
            field.parentNode.insertBefore(invalidFeedback, field.nextSibling);
        }

        // Update error message
        const errorText = invalidFeedback.querySelector('.error-text') || invalidFeedback;

        if (errors.length === 1) {
            errorText.textContent = errors[0];
        } else {
            errorText.innerHTML = '<ul>' +
                errors.map(e => `<li>${e}</li>`).join('') +
                '</ul>';
        }

        invalidFeedback.classList.add('show');
    }

    /**
     * Get field container (form-group, form-floating, etc)
     */
    getFieldContainer(field) {
        return field.closest('.form-group, .form-floating, .mb-3, .mb-4') || field.parentElement;
    }

    /**
     * Register validation rule for field
     */
    addRule(fieldName, validator) {
        if (!this.validationRules.has(fieldName)) {
            this.validationRules.set(fieldName, []);
        }
        this.validationRules.get(fieldName).push(validator);
    }

    /**
     * Register default validation rules
     */
    registerDefaultRules() {
        const inputs = this.form.querySelectorAll('[data-validate]');

        inputs.forEach(field => {
            const rules = field.getAttribute('data-validate').split(',').map(r => r.trim());

            rules.forEach(rule => {
                if (rule === 'required') {
                    this.addRule(field.name || field.id, {
                        validate: (value, elem) => {
                            if (elem.type === 'checkbox') {
                                return !elem.checked ? 'Kolom ini wajib dicentang' : null;
                            }
                            if (elem.type === 'radio') {
                                const form = elem.form || this.form;
                                const checkedRadio = form.querySelector(`input[name="${elem.name}"]:checked`);
                                return !checkedRadio ? 'Silakan pilih salah satu opsi' : null;
                            }
                            return !value ? 'Kolom ini wajib diisi' : null;
                        }
                    });
                }
                if (rule === 'email') {
                    this.addRule(field.name || field.id, {
                        validate: (value) => {
                            if (!value) return null;
                            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                            return !emailRegex.test(value) ? 'Format email tidak valid' : null;
                        }
                    });
                }
                if (rule === 'phone') {
                    this.addRule(field.name || field.id, {
                        validate: (value) => {
                            if (!value) return null;
                            const phoneRegex = /^(\+62|0)[0-9]{9,12}$/;
                            return !phoneRegex.test(value) ? 'Nomor telepon tidak valid' : null;
                        }
                    });
                }
                if (rule === 'nik') {
                    this.addRule(field.name || field.id, {
                        validate: (value) => {
                            if (!value) return null;
                            if (!/^\d{16}$/.test(value)) return 'NIK harus 16 digit';
                            return null;
                        }
                    });
                }
                if (rule.startsWith('minlength:')) {
                    const minLength = parseInt(rule.split(':')[1]);
                    this.addRule(field.name || field.id, {
                        validate: (value) => {
                            if (!value) return null;
                            return value.length < minLength ?
                                `Minimal ${minLength} karakter` : null;
                        }
                    });
                }
                if (rule.startsWith('maxlength:')) {
                    const maxLength = parseInt(rule.split(':')[1]);
                    this.addRule(field.name || field.id, {
                        validate: (value) => {
                            if (!value) return null;
                            return value.length > maxLength ?
                                `Maksimal ${maxLength} karakter` : null;
                        }
                    });
                }
            });
        });
    }

    /**
     * Validate entire form
     */
    validateForm() {
        const fields = this.form.querySelectorAll(
            'input[type="text"], input[type="email"], input[type="tel"], input[type="number"], ' +
            'input[type="date"], input[type="password"], select, textarea, input[type="checkbox"]:checked, input[type="radio"]:checked'
        );

        let allValid = true;
        const errors = [];

        fields.forEach(field => {
            const isValid = this.validateField(field);
            if (!isValid) {
                allValid = false;
                const state = this.fieldStates.get(field.name || field.id);
                errors.push({
                    field: field.name || field.id,
                    label: field.getAttribute('aria-label') || field.name,
                    errors: state.errors
                });
            }
        });

        // Show error summary if validation fails
        if (!allValid) {
            this.showErrorSummary(errors);
        } else {
            this.hideErrorSummary();
        }

        return allValid;
    }

    /**
     * Show error summary at top of form
     */
    showErrorSummary(errors) {
        let summary = this.form.querySelector('.form-error-summary');

        if (!summary) {
            summary = document.createElement('div');
            summary.className = 'form-error-summary';
            this.form.insertBefore(summary, this.form.firstChild);
        }

        let html = '<h4>Please fix the following errors:</h4><ul>';
        errors.forEach(error => {
            html += `<li><a href="#${error.field}">${error.label}: ${error.errors[0]}</a></li>`;
        });
        html += '</ul>';

        summary.innerHTML = html;
        summary.classList.add('show');

        // Scroll to summary
        summary.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /**
     * Hide error summary
     */
    hideErrorSummary() {
        const summary = this.form.querySelector('.form-error-summary');
        if (summary) {
            summary.classList.remove('show');
        }
    }

    /**
     * Handle form submit
     */
    handleFormSubmit(event) {
        if (!this.validateForm()) {
            event.preventDefault();
            return false;
        }

        return true;
    }

    /**
     * Update character counter for textarea
     */
    updateCharCounter(textarea) {
        const container = this.getFieldContainer(textarea);
        let counter = container.querySelector('.char-counter');

        if (!counter) {
            counter = document.createElement('div');
            counter.className = 'char-counter';
            textarea.parentNode.insertBefore(counter, textarea.nextSibling);
        }

        const current = textarea.value.length;
        const max = textarea.maxLength || 500;
        const percent = (current / max) * 100;

        counter.textContent = `${current}/${max}`;
        counter.classList.remove('warning', 'error');

        if (percent > 90) {
            counter.classList.add('error');
        } else if (percent > 75) {
            counter.classList.add('warning');
        }
    }

    /**
     * Show toast message
     */
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: ${type === 'error' ? 'var(--sp-danger)' : type === 'success' ? 'var(--sp-success)' : 'var(--sp-primary)'};
            color: white;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 0.9rem;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: slideInUp 0.3s ease;
        `;
        toast.textContent = message;

        document.body.appendChild(toast);

        // Auto-remove after 3 seconds
        setTimeout(() => {
            toast.style.animation = 'slideOutDown 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    /**
     * Reset form validation state
     */
    resetForm() {
        const fields = this.form.querySelectorAll(
            'input[type="text"], input[type="email"], input[type="tel"], input[type="number"], ' +
            'input[type="date"], input[type="password"], select, textarea'
        );

        fields.forEach(field => {
            field.classList.remove('is-valid', 'is-invalid');
            const container = this.getFieldContainer(field);
            container.querySelectorAll('.valid-feedback, .invalid-feedback').forEach(feedback => {
                feedback.classList.remove('show');
            });
        });

        this.hideErrorSummary();
        this.fieldStates.clear();
    }

    /**
     * Get validation state
     */
    getState() {
        return {
            isFormValid: Array.from(this.fieldStates.values()).every(state => state.isValid),
            fields: Object.fromEntries(this.fieldStates),
            errorCount: Array.from(this.fieldStates.values()).filter(state => !state.isValid).length
        };
    }

    /**
     * Focus on first invalid field
     */
    focusFirstInvalidField() {
        for (const [, state] of this.fieldStates) {
            if (!state.isValid) {
                const field = this.form.querySelector(`[name="${[...this.fieldStates.keys()][Array.from(this.fieldStates.values()).indexOf(state)]}"]`);
                if (field) field.focus();
                break;
            }
        }
    }
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInUp {
        from {
            transform: translateY(20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    @keyframes slideOutDown {
        from {
            transform: translateY(0);
            opacity: 1;
        }
        to {
            transform: translateY(20px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Initialize on load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.mobileFormValidator = new MobileFormValidator('form[name="registration"]');
    });
} else {
    window.mobileFormValidator = new MobileFormValidator('form[name="registration"]');
}

// Export for testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MobileFormValidator;
}
