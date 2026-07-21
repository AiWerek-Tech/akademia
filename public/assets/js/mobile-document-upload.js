/**
 * SPMB WMVAA - Mobile Document Upload Handler
 * Phase 1: Mobile Experience - Task #4
 * Version: 1.1
 * 
 * Purpose: Handle camera capture, image compression, and mobile-optimized uploads
 * Features: Camera integration, client-side compression, progress tracking
 */

class MobileDocumentUpload {
    constructor(options = {}) {
        this.options = {
            maxWidth: options.maxWidth || 1024,
            maxHeight: options.maxHeight || 1024,
            maxSize: options.maxSize || 2 * 1024 * 1024, // 2MB
            quality: options.quality || 0.85,
            uploadUrl: options.uploadUrl || '/pendaftar/dokumen/upload',
            documentType: options.documentType || 'identity',
            autoUpload: options.autoUpload !== false,
            ...options
        };

        this.files = {}; // Store files by document type: { type: { originalFile, compressedBlob } }
        this.isCompressing = false;
        this.uploadInProgress = false;
        this.stream = null;

        this.init();
    }

    /**
     * Initialize the upload handler
     */
    init() {
        this.attachEventListeners();
        this.detectMobileEnvironment();
    }

    /**
     * Detect if running on mobile
     */
    detectMobileEnvironment() {
        this.isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
        this.supportsCamera = this.isMobile && navigator.mediaDevices && navigator.mediaDevices.getUserMedia;
        
        // Keep capability detection silent on applicant pages.
    }

    /**
     * Attach event listeners to camera and file buttons
     */
    attachEventListeners() {
        // Camera button
        document.querySelectorAll('.btn-camera').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const docType = btn.getAttribute('data-type') || this.options.documentType;
                this.openCamera(docType);
            });
        });

        // File upload button
        document.querySelectorAll('.btn-file-upload').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const docType = btn.getAttribute('data-type') || this.options.documentType;
                this.openFileDialog(docType);
            });
        });

        // File input change
        const fileInput = document.getElementById('fileUploadInput');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                const docType = fileInput.dataset.documentType || this.options.documentType;
                this.handleFileSelect(e.target.files[0], docType);
            });
        }

        // Camera capture button
        const captureBtn = document.getElementById('captureBtn');
        if (captureBtn) {
            captureBtn.addEventListener('click', () => {
                this.capturePhoto();
            });
        }

        // Cancel camera button
        const cancelCameraBtn = document.getElementById('cancelCameraBtn');
        if (cancelCameraBtn) {
            cancelCameraBtn.addEventListener('click', () => {
                this.closeCamera();
            });
        }
    }

    /**
     * Open camera for photo capture
     */
    async openCamera(documentType) {
        if (!this.supportsCamera) {
            this.showError('Camera tidak tersedia di perangkat ini. Silakan gunakan upload file.');
            return;
        }

        try {
            // Request camera permission
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: false
            });

            // Show camera UI
            const cameraUI = document.getElementById('cameraUI');
            if (cameraUI) {
                cameraUI.style.display = 'block';
                const video = document.getElementById('cameraVideoPreview');
                if (video) {
                    video.srcObject = this.stream;
                    video.setAttribute('playsinline', 'true');
                    video.play().catch(err => {
                        console.error('[MobileDocumentUpload] Video play error:', err);
                        this.showError('Gagal memulai kamera');
                    });
                }
            }

            this.currentDocumentType = documentType;
        } catch (error) {
            console.error('[MobileDocumentUpload] Camera error:', error);

            if (error.name === 'NotAllowedError') {
                this.showError('Izin kamera ditolak. Silakan aktifkan kamera di pengaturan aplikasi.');
            } else if (error.name === 'NotFoundError') {
                this.showError('Kamera tidak ditemukan di perangkat ini.');
            } else {
                this.showError('Gagal mengakses kamera: ' + error.message);
            }
        }
    }

    /**
     * Capture photo from camera
     */
    capturePhoto() {
        const video = document.getElementById('cameraVideoPreview');
        const canvas = document.getElementById('captureCanvas');

        if (!video || !canvas) {
            this.showError('Elemen kamera tidak ditemukan');
            return;
        }

        try {
            const ctx = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            // Draw video frame to canvas
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Convert canvas to blob
            canvas.toBlob(
                (blob) => {
                    this.handleFileSelect(blob, this.currentDocumentType);
                    this.closeCamera();
                },
                'image/jpeg',
                this.options.quality
            );
        } catch (error) {
            console.error('[MobileDocumentUpload] Capture error:', error);
            this.showError('Gagal mengambil foto');
        }
    }

    /**
     * Close camera and clean up
     */
    closeCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }

        const cameraUI = document.getElementById('cameraUI');
        if (cameraUI) {
            cameraUI.style.display = 'none';
        }
    }

    /**
     * Open file dialog for upload
     */
    openFileDialog(documentType) {
        const fileInput = document.getElementById('fileUploadInput');
        if (fileInput) {
            fileInput.dataset.documentType = documentType;
            fileInput.click();
        }
    }

    /**
     * Handle file selection (from camera or file dialog)
     */
    async handleFileSelect(file, type) {
        if (!file) return;

        const docType = type || this.currentDocumentType || this.options.documentType;
        this.files[docType] = {
            originalFile: file,
            compressedBlob: null
        };

        // Show compression status
        this.showCompressionStatus(docType, 'compressing');

        try {
            // Compress image (or bypass if PDF)
            const compressedBlob = await this.compressImage(file);
            this.files[docType].compressedBlob = compressedBlob;

            // Show preview
            this.showPreview(docType, compressedBlob);

            if (this.options.autoUpload) {
                const uploadResult = await this.uploadFile(docType);
                if (!uploadResult?.success) {
                    this.showUploadButton(docType);
                }
            } else {
                this.showUploadButton(docType);
            }
        } catch (error) {
            console.error('[MobileDocumentUpload] Processing error for type ' + docType + ':', error);
            this.showError('Gagal memproses file: ' + error.message);
            this.showCompressionStatus(docType, 'error');
        }
    }

    /**
     * Compress image using Canvas API
     */
    async compressImage(file) {
        // Bypass compression for PDF files
        if (file.type === 'application/pdf' || (file.name && file.name.toLowerCase().endsWith('.pdf'))) {
            if (file.size > this.options.maxSize) {
                throw new Error(`Ukuran file PDF (${this.formatFileSize(file.size)}) melebihi batas maksimal (${this.formatFileSize(this.options.maxSize)})`);
            }
            return file;
        }

        return new Promise((resolve, reject) => {
            const reader = new FileReader();

            reader.onload = (e) => {
                const img = new Image();

                img.onload = () => {
                    try {
                        // Calculate new dimensions (maintain aspect ratio)
                        let width = img.width;
                        let height = img.height;

                        if (width > height) {
                            if (width > this.options.maxWidth) {
                                height = Math.round(height * (this.options.maxWidth / width));
                                width = this.options.maxWidth;
                            }
                        } else {
                            if (height > this.options.maxHeight) {
                                width = Math.round(width * (this.options.maxHeight / height));
                                height = this.options.maxHeight;
                            }
                        }

                        // Create canvas and draw image
                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;

                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);

                        // Convert to blob with quality setting
                        canvas.toBlob(
                            (blob) => {
                                if (blob.size > this.options.maxSize) {
                                    reject(new Error(`Ukuran file (${this.formatFileSize(blob.size)}) melebihi batas maksimal (${this.formatFileSize(this.options.maxSize)})`));
                                } else {
                                    resolve(blob);
                                }
                            },
                            'image/jpeg',
                            this.options.quality
                        );
                    } catch (error) {
                        reject(error);
                    }
                };

                img.onerror = () => {
                    reject(new Error('Format file gambar tidak valid atau rusak'));
                };

                img.src = e.target.result;
            };

            reader.onerror = () => {
                reject(new Error('Gagal membaca file'));
            };

            reader.readAsDataURL(file);
        });
    }

    /**
     * Show image preview
     */
    showPreview(type, blob) {
        const fileData = this.files[type];
        if (!fileData) return;
        
        const originalFile = fileData.originalFile;
        const isPdf = originalFile.type === 'application/pdf' || (originalFile.name && originalFile.name.toLowerCase().endsWith('.pdf'));
        
        const previewContainer = document.getElementById('previewContainer_' + type);
        const previewImg = document.getElementById('previewImage_' + type);
        const previewEmpty = previewContainer ? previewContainer.querySelector('.preview-empty') : null;

        if (previewContainer) {
            previewContainer.style.display = 'block';
        }

        if (isPdf) {
            if (previewImg) {
                previewImg.style.display = 'none';
            }
            if (previewEmpty) {
                previewEmpty.style.display = 'flex';
                const emptyIcon = previewEmpty.querySelector('[data-lucide]');
                if (emptyIcon) {
                    emptyIcon.setAttribute('data-lucide', 'file-text');
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                }
                const emptyText = previewEmpty.querySelector('.preview-empty-text');
                if (emptyText) {
                    emptyText.textContent = 'Dokumen PDF siap diunggah';
                }
            }
        } else {
            if (previewEmpty) {
                previewEmpty.style.display = 'none';
            }
            if (previewImg) {
                previewImg.style.display = 'block';
                const url = URL.createObjectURL(blob);
                previewImg.src = url;
                previewImg.onload = () => {
                    URL.revokeObjectURL(url); // Clean up object URL after loading
                };
            }
        }

        // Show file info
        this.updateFileInfo(type, originalFile, blob);
        if (isPdf) {
            this.showCompressionStatus(type, 'PDF (Tanpa Kompresi)');
        } else {
            this.showCompressionStatus(type, 'success', Math.round((1 - blob.size / originalFile.size) * 100));
        }
    }

    /**
     * Update file info display
     */
    updateFileInfo(type, originalFile, blob) {
        const filenameEl = document.getElementById('previewFilename_' + type);
        const sizeEl = document.getElementById('previewFilesize_' + type);

        if (filenameEl) {
            filenameEl.textContent = originalFile.name || 'camera_capture.jpg';
        }

        if (sizeEl) {
            const isPdf = originalFile.type === 'application/pdf' || (originalFile.name && originalFile.name.toLowerCase().endsWith('.pdf'));
            if (isPdf) {
                sizeEl.textContent = this.formatFileSize(blob.size);
            } else {
                sizeEl.textContent = `${this.formatFileSize(originalFile.size)} → ${this.formatFileSize(blob.size)}`;
            }
        }
    }

    /**
     * Show compression status message
     */
    showCompressionStatus(type, status, reduction = 0) {
        const statusEl = document.getElementById('compressionStatus_' + type);
        if (!statusEl) return;

        let message = '';
        let className = '';

        if (status === 'PDF (Tanpa Kompresi)') {
            message = '✓ PDF (Tanpa Kompresi)';
            className = 'success';
        } else {
            switch (status) {
                case 'compressing':
                    message = '⏳ Memproses gambar...';
                    className = '';
                    break;
                case 'success':
                    message = `✓ Gambar dioptimalkan (Pengurangan: ${reduction}%)`;
                    className = 'success';
                    break;
                case 'error':
                    message = '✗ Gagal memproses gambar';
                    className = 'error';
                    break;
                default:
                    message = status;
            }
        }

        statusEl.textContent = message;
        statusEl.className = `image-compression-status ${className}`.trim();
    }

    /**
     * Show upload button
     */
    showUploadButton(type) {
        const uploadBtn = document.getElementById('uploadBtn_' + type);
        const clearBtn = document.getElementById('clearPreviewBtn_' + type);

        if (uploadBtn) {
            uploadBtn.style.display = 'inline-block';
            uploadBtn.onclick = () => this.uploadFile(type);
        }

        if (clearBtn) {
            clearBtn.style.display = 'inline-block';
            clearBtn.onclick = () => this.clearPreview(type);
        }
    }

    /**
     * Check whether any selected files are still waiting to be uploaded.
     */
    hasPendingUploads() {
        return Object.keys(this.files).some((type) => this.files[type]?.compressedBlob);
    }

    /**
     * Upload every file that has been selected but not yet sent to the server.
     */
    async uploadAllPending() {
        const pendingTypes = Object.keys(this.files).filter((type) => this.files[type]?.compressedBlob);

        if (pendingTypes.length === 0) {
            return { success: true, uploadedCount: 0 };
        }

        for (const type of pendingTypes) {
            const result = await this.uploadFile(type, {
                reloadOnSuccess: false,
                showSuccessToast: false,
            });

            if (!result?.success) {
                return {
                    success: false,
                    message: result?.message || `Gagal mengunggah dokumen "${type}".`,
                    failedType: type,
                };
            }
        }

        return { success: true, uploadedCount: pendingTypes.length };
    }

    /**
     * Upload file to server
     */
    async uploadFile(type, options = {}) {
        const { reloadOnSuccess = true, showSuccessToast = true } = options;
        const fileData = this.files && this.files[type];
        if (!fileData || !fileData.compressedBlob) {
            const message = 'Tidak ada file yang dipilih';
            this.showError(message);
            return { success: false, message };
        }

        if (this.uploadInProgress) {
            return { success: false, message: 'Upload sedang berlangsung, tunggu sebentar.' };
        }

        this.uploadInProgress = true;
        this.setUploadButtonState(type, 'uploading');

        try {
            // Show upload progress
            this.showUploadProgress(type);

            // Get CSRF token
            const csrfToken = document.querySelector('input[name="csrf_token"]')?.value ||
                            document.querySelector('input[name="XSRF-TOKEN"]')?.value;

            // Create form data
            const formData = new FormData();
            formData.append('document_file', fileData.compressedBlob, fileData.originalFile.name || 'camera_capture.jpg');
            formData.append('document_type', type || this.options.documentType);
            if (csrfToken) {
                formData.append('csrf_token', csrfToken);
            }

            const progressFill = document.getElementById('uploadProgressFill_' + type);
            const progressPercent = document.getElementById('uploadProgressPercent_' + type);

            // Simulate progress (since we can't track actual upload progress with fetch)
            const progressInterval = setInterval(() => {
                if (progressFill) {
                    const currentWidth = parseInt(progressFill.style.width) || 0;
                    if (currentWidth < 90) {
                        const newWidth = Math.round(currentWidth + Math.random() * 20);
                        progressFill.style.width = newWidth + '%';
                        if (progressPercent) {
                            progressPercent.textContent = newWidth + '%';
                        }
                    }
                }
            }, 200);

            // Upload file
            const response = await fetch(this.options.uploadUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            clearInterval(progressInterval);

            const result = await response.json();

            // Update CSRF token if returned
            if (result.csrf_token) {
                const csrfInputs = document.querySelectorAll('input[name="csrf_token"]');
                csrfInputs.forEach(input => {
                    input.value = result.csrf_token;
                });
            }

            if (result.success) {
                // Complete progress
                if (progressFill) {
                    progressFill.style.width = '100%';
                }
                if (progressPercent) {
                    progressPercent.textContent = '100%';
                }

                delete this.files[type];
                this.setUploadButtonState(type, 'success');

                if (showSuccessToast) {
                    this.showSuccess('Dokumen berhasil diunggah');
                }

                if (reloadOnSuccess) {
                    setTimeout(() => {
                        window.location.reload();
                    }, showSuccessToast ? 1500 : 0);
                }

                return { success: true };
            }

            const message = result.message || 'Gagal mengunggah dokumen';
            this.showError(message);
            this.setUploadButtonState(type, 'ready');
            const progressContainer = document.getElementById('uploadProgressContainer_' + type);
            if (progressContainer) {
                progressContainer.style.display = 'none';
            }
            return { success: false, message };
        } catch (error) {
            console.error('[MobileDocumentUpload] Upload error:', error);
            const message = 'Gagal mengunggah: ' + error.message;
            this.showError(message);
            this.setUploadButtonState(type, 'ready');
            const progressContainer = document.getElementById('uploadProgressContainer_' + type);
            if (progressContainer) {
                progressContainer.style.display = 'none';
            }
            return { success: false, message };
        } finally {
            this.uploadInProgress = false;
        }
    }

    /**
     * Reflect upload state on the manual action button.
     */
    setUploadButtonState(type, state) {
        const uploadBtn = document.getElementById('uploadBtn_' + type);
        if (!uploadBtn) {
            return;
        }

        uploadBtn.style.display = 'inline-block';
        uploadBtn.disabled = state === 'uploading';

        if (state === 'uploading') {
            uploadBtn.innerHTML = '<i data-lucide="loader-2"></i> Mengunggah...';
        } else {
            uploadBtn.innerHTML = '<i data-lucide="check"></i> Upload Dokumen';
        }

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    /**
     * Show upload progress bar
     */
    showUploadProgress(type) {
        const progressContainer = document.getElementById('uploadProgressContainer_' + type);
        if (progressContainer) {
            progressContainer.style.display = 'block';
            const progressFill = document.getElementById('uploadProgressFill_' + type);
            if (progressFill) {
                progressFill.style.width = '10%';
            }
            const progressPercent = document.getElementById('uploadProgressPercent_' + type);
            if (progressPercent) {
                progressPercent.textContent = '10%';
            }
        }
    }

    /**
     * Clear preview
     */
    clearPreview(type) {
        if (!type) return;

        if (this.files && this.files[type]) {
            delete this.files[type];
        }

        // Clear UI
        const previewContainer = document.getElementById('previewContainer_' + type);
        if (previewContainer) {
            previewContainer.style.display = 'none';
        }

        const previewImg = document.getElementById('previewImage_' + type);
        if (previewImg) {
            previewImg.src = '';
            previewImg.style.display = 'none';
        }

        const previewEmpty = previewContainer ? previewContainer.querySelector('.preview-empty') : null;
        if (previewEmpty) {
            previewEmpty.style.display = 'flex';
            const emptyIcon = previewEmpty.querySelector('[data-lucide]');
            if (emptyIcon) {
                emptyIcon.setAttribute('data-lucide', 'image');
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }
            const emptyText = previewEmpty.querySelector('.preview-empty-text');
            if (emptyText) {
                emptyText.textContent = 'Pratinjau gambar';
            }
        }

        const statusEl = document.getElementById('compressionStatus_' + type);
        if (statusEl) {
            statusEl.textContent = '';
            statusEl.className = 'image-compression-status';
        }

        const uploadBtn = document.getElementById('uploadBtn_' + type);
        if (uploadBtn) {
            uploadBtn.style.display = 'none';
        }

        const clearBtn = document.getElementById('clearPreviewBtn_' + type);
        if (clearBtn) {
            clearBtn.style.display = 'none';
        }

        const fileInput = document.getElementById('fileUploadInput');
        if (fileInput) {
            fileInput.value = '';
        }

    }

    /**
     * Show error message
     */
    showError(message) {
        console.error('[MobileDocumentUpload]', message);
        
        // Try to use SweetAlert if available
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonColor: '#667eea'
            });
        } else {
            alert('❌ ' + message);
        }
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: message,
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            alert('✓ ' + message);
        }
    }

    /**
     * Format file size for display
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    /**
     * Cleanup on page unload
     */
    destroy() {
        this.closeCamera();
        this.files = {};
    }
}

// Export for testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MobileDocumentUpload;
}
