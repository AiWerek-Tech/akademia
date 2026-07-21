/**
 * SPMB WMVAA - Form Wizard Draft Manager
 * Phase 1: Mobile Optimization - Autosave & Draft Recovery
 * Version: 1.1
 * 
 * Features:
 * - Auto-save form data to localStorage with user isolation (userId)
 * - Recover draft on page reload with SweetAlert2 confirmation
 * - Sync local changes with server every 30 seconds
 * - Show draft status indicator (cloud/database icons)
 * - Handle offline/online transitions
 */

class WizardDraftManager {
    constructor(options = {}) {
        this.formId = options.formId || '#stepForm' + (options.currentStep || 1);
        this.userId = options.userId || null;
        this.currentStep = options.currentStep || 1;
        this.maxSteps = 9;
        
        // Storage keys with user isolation
        this.storageKey = `spmb_draft_step_${this.currentStep}_user_${this.userId}`;
        this.timestampKey = `${this.storageKey}_ts`;
        
        this.serverSaveUrl = options.serverSaveUrl || '';
        this.autoSaveInterval = options.autoSaveInterval || 5000; // Local save check/interval
        this.serverSyncInterval = options.serverSyncInterval || 30000; // Server sync interval (30s)
        
        this.isOnline = navigator.onLine;
        this.isDirty = false; // Has changes not synced to server
        this.lastSaveTime = null;
        this.syncInterval = null;
        
        this.init();
    }

    /**
     * Initialize the draft manager
     */
    init() {
        this.attachEventListeners();
        this.checkForLocalDraft();
        // Disable server autosync to prevent CSRF issues
        // this.startAutoSync();
        // this.handleOnlineOfflineTransitions();
        
        // Initial status update: if there is a local draft, show local status
        const draftExists = localStorage.getItem(this.storageKey);
        if (draftExists) {
            this.updateDraftStatus('local');
        } else {
            this.updateDraftStatus('saved');
        }
    }

    /**
     * Attach event listeners to form inputs
     */
    attachEventListeners() {
        const form = document.querySelector(this.formId);
        if (!form) {
            console.warn(`Form ${this.formId} not found`);
            return;
        }

        // Listen to all input changes
        form.addEventListener('input', (e) => {
            this.isDirty = true;
            this.saveToLocalStorage();
            this.updateProgressBar();
        });

        form.addEventListener('change', (e) => {
            this.isDirty = true;
            this.saveToLocalStorage();
            this.updateProgressBar();
        });
    }

    /**
     * Start server auto-sync interval
     */
    startAutoSync() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
        }

        if (this.serverSaveUrl) {
            this.syncInterval = setInterval(() => {
                if (this.isDirty && this.isOnline) {
                    this.syncToServer();
                }
            }, this.serverSyncInterval);
        }
    }

    /**
     * Save current form to localStorage
     */
    saveToLocalStorage() {
        const form = document.querySelector(this.formId);
        if (!form) return;

        const formData = new FormData(form);
        const data = {};

        for (let [key, value] of formData.entries()) {
            const input = form.querySelector(`[name="${key}"]`);
            if (input && input.type !== 'file' && input.type !== 'password') {
                if (data[key]) {
                    if (Array.isArray(data[key])) {
                        data[key].push(value);
                    } else {
                        data[key] = [data[key], value];
                    }
                } else {
                    data[key] = value;
                }
            }
        }

        try {
            localStorage.setItem(this.storageKey, JSON.stringify(data));
            localStorage.setItem(this.timestampKey, Date.now().toString());
            this.isDirty = true;
            this.updateDraftStatus('local');
        } catch (error) {
            console.error('Failed to save draft locally:', error);
        }
    }

    /**
     * Sync local draft data to the server
     */
    async syncToServer() {
        if (!this.serverSaveUrl) return;

        const form = document.querySelector(this.formId);
        if (!form) return;

        this.updateDraftStatus('saving');
        const formData = new FormData(form);

        try {
            // Get CSRF token from window if available
            let csrfName = 'csrf_token';
            let csrfHash = '';
            if (typeof window !== 'undefined') {
                // Try to get dynamic csrf name from window or form
                const csrfInput = form.querySelector('input[name*="csrf"]');
                if (csrfInput) {
                    csrfName = csrfInput.name;
                    csrfHash = csrfInput.value;
                }
            }

            const response = await fetch(this.serverSaveUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfHash
                },
                body: formData
            });

            const result = await response.json();
            if (result.csrf_token && typeof window.updateCsrfToken === 'function') {
                window.updateCsrfToken(result.csrf_token);
            }

            if (result.success) {
                this.isDirty = false;
                this.clearLocalDraft();
                this.updateDraftStatus('saved');
            } else {
                this.updateDraftStatus('offline');
                console.warn('Server sync refused:', result.message || result.errors);
            }
        } catch (error) {
            console.error('Server sync failed:', error);
            this.updateDraftStatus('offline');
        }
    }

    /**
     * Recover draft from localStorage
     */
    checkForLocalDraft() {
        const draftStr = localStorage.getItem(this.storageKey);
        const tsStr = localStorage.getItem(this.timestampKey);
        if (!draftStr || !tsStr) return;

        const timestamp = parseInt(tsStr, 10);
        const data = JSON.parse(draftStr);

        // Check if draft is older than 24 hours
        const ageMs = Date.now() - timestamp;
        if (ageMs > 24 * 60 * 60 * 1000) {
            this.clearLocalDraft();
            return;
        }

        const form = document.querySelector(this.formId);
        if (!form) return;

        // Check if the form currently has different/empty values compared to the draft
        let hasNewerData = false;
        for (const [key, val] of Object.entries(data)) {
            const field = form.querySelector(`[name="${key}"]`);
            if (field) {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    if (field.type === 'checkbox' && field.checked !== (val === '1' || val === 'on' || val === true)) {
                        hasNewerData = true;
                        break;
                    } else if (field.type === 'radio') {
                        const activeRadio = form.querySelector(`[name="${key}"]:checked`);
                        if (!activeRadio || activeRadio.value !== val) {
                            hasNewerData = true;
                            break;
                        }
                    }
                } else if (val && !field.value) {
                    hasNewerData = true;
                    break;
                }
            }
        }

        if (hasNewerData) {
            const primaryColor = window.SpTheme ? window.SpTheme.getThemePrimary() : getComputedStyle(document.documentElement).getPropertyValue('--sp-primary').trim() || '#6366f1';
            
            const swalOptions = {
                icon: 'info',
                title: 'Pulihkan Draft?',
                text: 'Ditemukan draf pengisian sebelumnya yang belum tersimpan di server. Apakah Anda ingin memulihkannya?',
                showCancelButton: true,
                confirmButtonText: 'Ya, Pulihkan',
                cancelButtonText: 'Mulai Baru',
                confirmButtonColor: primaryColor,
                cancelButtonColor: '#64748b'
            };

            const finalOptions = window.SpTheme ? window.SpTheme.mergeSwalOptions(swalOptions) : swalOptions;

            if (typeof Swal !== 'undefined') {
                Swal.fire(finalOptions).then((result) => {
                    if (result.isConfirmed) {
                        this.restoreLocalDraft(data);
                    } else {
                        this.clearLocalDraft();
                        this.updateDraftStatus('saved');
                    }
                });
            } else {
                if (confirm('Pulihkan Draft? Ditemukan draf pengisian sebelumnya yang belum tersimpan di server.')) {
                    this.restoreLocalDraft(data);
                } else {
                    this.clearLocalDraft();
                    this.updateDraftStatus('saved');
                }
            }
        }
    }

    /**
     * Restore localized draft data into form fields
     */
    restoreLocalDraft(data) {
        const form = document.querySelector(this.formId);
        if (!form) return;

        for (const [key, val] of Object.entries(data)) {
            const field = form.querySelector(`[name="${key}"]`);
            if (!field) continue;

            if (field.type === 'checkbox') {
                field.checked = (val === '1' || val === 'on' || val === true);
            } else if (field.type === 'radio') {
                const radio = form.querySelector(`[name="${key}"][value="${val}"]`);
                if (radio) radio.checked = true;
            } else {
                field.value = val;
                // Trigger change event for listeners
                if (typeof $ !== 'undefined') {
                    $(field).trigger('change');
                } else {
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        }

        this.isDirty = true;
        this.updateDraftStatus('local');

        const successSwal = {
            icon: 'success',
            title: 'Draft Dipulihkan',
            text: 'Data draft lokal berhasil dimasukkan ke form.',
            timer: 1500,
            showConfirmButton: false
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire(window.SpTheme ? window.SpTheme.mergeSwalOptions(successSwal) : successSwal);
        } else {
            alert('✓ Draft Dipulihkan');
        }
    }

    /**
     * Clear draft data from local storage
     */
    clearLocalDraft() {
        try {
            localStorage.removeItem(this.storageKey);
            localStorage.removeItem(this.timestampKey);
        } catch (error) {
            console.error('Failed to clear local draft:', error);
        }
    }

    /**
     * Update draft status indicator
     */
    updateDraftStatus(state) {
        const statusEl = document.getElementById('draftStatus');
        if (!statusEl) return;

        let html = '';
        if (state === 'saved') {
            html = '<i data-lucide="cloud" class="me-1 text-success" style="width:14px;height:14px;"></i> Draft tersimpan';
        } else if (state === 'saving') {
            html = '<span class="spinner-border spinner-border-sm me-1 text-primary" role="status" style="width:12px;height:12px;"></span> Menyimpan...';
        } else if (state === 'local') {
            html = '<i data-lucide="database" class="me-1 text-warning" style="width:14px;height:14px;"></i> Draft tersimpan lokal';
        } else if (state === 'offline' || state === 'error') {
            html = '<i data-lucide="cloud-off" class="me-1 text-danger" style="width:14px;height:14px;"></i> Gagal sinkronisasi';
        }

        statusEl.innerHTML = html;
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    /**
     * Handle online/offline transitions
     */
    handleOnlineOfflineTransitions() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            if (this.isDirty) {
                this.syncToServer();
            } else {
                this.updateDraftStatus('saved');
            }
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.updateDraftStatus('offline');
        });
    }

    /**
     * Update progress bar on mobile
     */
    updateProgressBar() {
        const progressPercentEl = document.getElementById('progressPercent');
        const progressFillEl = document.getElementById('progressFill');
        
        if (!progressPercentEl || !progressFillEl) return;

        // Calculate percentage (currently at step = percent)
        const percentage = Math.round((this.currentStep / this.maxSteps) * 100);
        progressPercentEl.textContent = percentage;
        progressFillEl.style.width = percentage + '%';
    }

    /**
     * Initialize progress bar on page load
     */
    static initializeProgressBar(currentStep = 1) {
        const percentage = Math.round((currentStep / 9) * 100);
        
        const currentStepEl = document.getElementById('currentStep');
        const progressPercentEl = document.getElementById('progressPercent');
        const progressFillEl = document.getElementById('progressFill');

        if (currentStepEl) currentStepEl.textContent = currentStep;
        if (progressPercentEl) progressPercentEl.textContent = percentage;
        if (progressFillEl) progressFillEl.style.width = percentage + '%';
    }

    /**
     * Cleanup resources
     */
    destroy() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
        }
    }
}

/**
 * Form Validation Helper (Client-side)
 * Phase 1: Mobile Optimization - Instant feedback
 */
class FormValidator {
    /**
     * Validate NIK format (16 digits)
     */
    static validateNIK(value) {
        const cleanValue = value.replace(/\D/g, '');
        return cleanValue.length === 16;
    }

    /**
     * Validate NISN format (10 digits)
     */
    static validateNISN(value) {
        const cleanValue = value.replace(/\D/g, '');
        return cleanValue.length === 10;
    }

    /**
     * Validate email format
     */
    static validateEmail(value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(value);
    }

    /**
     * Validate phone number (10-13 digits)
     */
    static validatePhone(value) {
        const cleanValue = value.replace(/\D/g, '');
        return cleanValue.length >= 10 && cleanValue.length <= 13;
    }

    /**
     * Add real-time validation to field
     */
    static addRealtimeValidation(fieldElement, validatorFn, errorMessage) {
        if (!fieldElement) return;

        fieldElement.addEventListener('blur', () => {
            const isValid = validatorFn(fieldElement.value);
            
            if (isValid) {
                fieldElement.classList.remove('is-invalid');
                fieldElement.classList.add('is-valid');
            } else {
                fieldElement.classList.remove('is-valid');
                fieldElement.classList.add('is-invalid');
                
                // Show error message below field
                let errorEl = fieldElement.nextElementSibling;
                if (!errorEl || !errorEl.classList.contains('invalid-feedback')) {
                    errorEl = document.createElement('div');
                    errorEl.className = 'invalid-feedback d-block';
                    fieldElement.parentNode.insertBefore(errorEl, fieldElement.nextSibling);
                }
                errorEl.textContent = errorMessage;
            }
        });
    }

    /**
     * Setup all field validations
     */
    static setupFormValidations() {
        // NIK validation
        const nikField = document.querySelector('[name="nik"]');
        if (nikField) {
            this.addRealtimeValidation(
                nikField,
                (value) => this.validateNIK(value),
                'NIK harus 16 digit'
            );
        }

        // NISN validation
        const nisnField = document.querySelector('[name="nisn"]');
        if (nisnField) {
            this.addRealtimeValidation(
                nisnField,
                (value) => this.validateNISN(value),
                'NISN harus 10 digit'
            );
        }

        // Email validation
        const emailField = document.querySelector('[name="email"]');
        if (emailField) {
            this.addRealtimeValidation(
                emailField,
                (value) => !value || this.validateEmail(value),
                'Format email tidak valid'
            );
        }

        // Phone validation
        const phoneFields = document.querySelectorAll('[type="tel"]');
        phoneFields.forEach(field => {
            this.addRealtimeValidation(
                field,
                (value) => !value || this.validatePhone(value),
                'Nomor HP harus 10-13 digit'
            );
        });
    }
}

// Export for testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { WizardDraftManager, FormValidator };
}
