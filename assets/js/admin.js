/**
 * Product Availability Checker Admin JavaScript
 * 
 * Handles CRUD operations, search functionality, and modal interactions
 * for the availability codes management interface.
 */

(function ($) {
    'use strict';

    /**
     * Admin Controller Class
     */
    class PAVCAdmin {
        constructor() {
            this.baseUrl = 'pavc/v1/codes';
            this.searchQuery = '';
            this.isLoading = false;
            this.editingCodeId = null;

            // Initialize strings from localized data with fallbacks
            this.strings = {};
            if (typeof pavcAdmin !== 'undefined' && pavcAdmin.strings) {
                this.strings = pavcAdmin.strings;
            } else {
                // Fallback strings if localization fails
                this.strings = {
                    confirmDelete: 'Are you sure you want to delete this code?',
                    success: 'Operation completed successfully.',
                    error: 'An error occurred. Please try again.',
                    addCode: 'Add Code',
                    editCode: 'Edit Code',
                    deleteCode: 'Delete',
                    available: 'Available',
                    unavailable: 'Unavailable',
                    noCodes: 'No codes found.',
                    noCustomMessage: 'No custom message set'
                };
            }

            this.init();
        }

        /**
         * Initialize the admin functionality
         */
        init() {
            this.bindEvents();
        }

        /**
         * Bind event handlers
         */
        bindEvents() {
            // Add new code button
            $(document).on('click', '#pavc-add-code', this.openAddModal.bind(this));

            // Edit code buttons
            $(document).on('click', '.pavc-edit-code', this.openEditModal.bind(this));

            // Delete code buttons
            $(document).on('click', '.pavc-delete-code', this.deleteCode.bind(this));

            // Modal close buttons
            $(document).on('click', '.pavc-modal-close, .pavc-modal-cancel', this.closeModal.bind(this));

            // Save code button
            $(document).on('click', '#pavc-save-code', this.saveCode.bind(this));

            // Search functionality
            $(document).on('input', '#pavc-search-codes', this.debounce(this.handleSearch.bind(this), 300));

            // Modal overlay click to close
            $(document).on('click', '#pavc-code-modal', function (e) {
                if (e.target === this) {
                    this.closeModal();
                }
            }.bind(this));

            // Prevent form submission inside modal
            $(document).on('submit', '#pavc-code-form', function (e) {
                e.preventDefault();
                return false;
            });

            // Enter key handling in modal
            $(document).on('keydown', '#pavc-code-modal', function (e) {
                if (e.key === 'Enter' && !$(e.target).is('textarea')) {
                    e.preventDefault();
                    this.saveCode();
                }
                if (e.key === 'Escape') {
                    this.closeModal();
                }
            }.bind(this));
        }



        /**
         * Load codes from API
         */
        async loadCodes() {
            if (this.isLoading) return;

            this.showLoading();
            this.isLoading = true;

            try {
                const params = new URLSearchParams({
                    search: this.searchQuery
                });

                const response = await wp.apiFetch({
                    path: `${this.baseUrl}?${params}`
                });

                this.renderCodesTable(response);

                // Hide loading after DOM updates are complete
                setTimeout(() => {
                    this.hideLoading();
                    this.isLoading = false;
                }, 100);
            } catch (error) {
                this.showError(this.strings.error);
                console.error('Error loading codes:', error);
                this.hideLoading();
                this.isLoading = false;
            }
        }

        /**
         * Render codes table
         */
        renderCodesTable(codes) {
            const tbody = $('#pavc-codes-tbody');
            tbody.empty();

            if (!codes || codes.length === 0) {
                tbody.html(`
                    <tr class="no-items">
                        <td class="colspanchange" colspan="4">
                            ${this.strings.noCodes || 'No codes found.'}
                        </td>
                    </tr>
                `);
                return;
            }

            codes.forEach(code => {
                const row = this.createCodeRow(code);
                tbody.append(row);
            });
        }

        /**
         * Create a single code table row
         */
        createCodeRow(code) {
            const statusClass = code.availability === 'available' ? 'available' : 'unavailable';
            const statusText = code.availability === 'available' ?
                this.strings.available : this.strings.unavailable;

            // Format message - show placeholder if empty
            let messageContent = '';
            if (code.message && code.message.trim()) {
                messageContent = this.escapeHtml(code.message);
            } else {
                messageContent = `<em style="color: #666;">${this.strings.noCustomMessage}</em>`;
            }

            return $(`
                <tr data-code-id="${code.id}">
                    <td class="column-id">
                        <strong>${code.id}</strong>
                    </td>
                    <td class="column-code">
                        <strong>${this.escapeHtml(code.zip_code)}</strong>
                        <div>
                            <span class="pavc-status pavc-status-${statusClass}">
                                ${statusText}
                            </span>
                        </div>
                    </td>
                    <td class="column-message">
                        ${messageContent}
                    </td>
                    <td class="column-actions">
                        <button type="button" class="button button-small pavc-edit-code" data-code-id="${code.id}">
                            ${this.strings.editCode || 'Edit'}
                        </button>
                        <button type="button" class="button button-small button-link-delete pavc-delete-code" data-code-id="${code.id}">
                            ${this.strings.deleteCode || 'Delete'}
                        </button>
                    </td>
                </tr>
            `);
        }

        /**
         * Open add new code modal
         */
        openAddModal() {
            this.editingCodeId = null;
            this.resetForm();
            $('#pavc-modal-title').text(this.strings.addCode || 'Add New Code');
            this.showModal();
        }

        /**
         * Open edit code modal
         */
        async openEditModal(e) {
            const codeId = $(e.currentTarget).data('code-id');
            this.editingCodeId = codeId;

            try {
                this.showLoading();
                const code = await wp.apiFetch({
                    path: `${this.baseUrl}/${codeId}`
                });

                this.populateForm(code);
                $('#pavc-modal-title').text(this.strings.editCode || 'Edit');
                this.showModal();
            } catch (error) {
                this.showError(this.strings.error);
                console.error('Error loading code:', error);
            } finally {
                this.hideLoading();
            }
        }

        /**
         * Save code (create or update)
         */
        async saveCode() {
            const formData = this.getFormData();

            if (!this.validateForm(formData)) {
                return;
            }

            try {
                this.showLoading();

                let response;
                if (this.editingCodeId) {
                    // Update existing code
                    response = await wp.apiFetch({
                        path: `${this.baseUrl}/${this.editingCodeId}`,
                        method: 'PUT',
                        data: formData
                    });
                } else {
                    // Create new code
                    response = await wp.apiFetch({
                        path: this.baseUrl,
                        method: 'POST',
                        data: formData
                    });
                }

                this.showSuccess(this.strings.success);
                this.closeModal();
                this.loadCodes(); // Reload the table
            } catch (error) {
                let errorMessage = this.strings.error;
                if (error.message) {
                    errorMessage = error.message;
                }
                this.showError(errorMessage);
                console.error('Error saving code:', error);
            } finally {
                this.hideLoading();
            }
        }

        /**
         * Delete code
         */
        async deleteCode(e) {
            const codeId = $(e.currentTarget).data('code-id');

            if (!confirm(this.strings.confirmDelete)) {
                return;
            }

            try {
                this.showLoading();

                await wp.apiFetch({
                    path: `${this.baseUrl}/${codeId}`,
                    method: 'DELETE'
                });

                this.showSuccess(this.strings.success);
                this.loadCodes(); // Reload the table
            } catch (error) {
                this.showError(this.strings.error);
                console.error('Error deleting code:', error);
            } finally {
                this.hideLoading();
            }
        }

        /**
         * Handle search input
         */
        handleSearch(e) {
            this.searchQuery = e.target.value.trim();
            this.loadCodes();
        }



        /**
         * Get form data
         */
        getFormData() {
            const codeInput = $('#pavc-code-input');
            const statusSelect = $('#pavc-status-select');
            const messageInput = $('#pavc-message-input');

            return {
                zip_code: codeInput.length ? codeInput.val().trim() : '',
                availability: statusSelect.length ? statusSelect.val() : '',
                message: messageInput.length ? messageInput.val().trim() : ''
            };
        }

        /**
         * Validate form data
         */
        validateForm(data) {
            if (!data.zip_code) {
                this.showError('Code is required.');
                $('#pavc-code-input').focus();
                return false;
            }

            if (!data.availability) {
                this.showError('Status is required.');
                $('#pavc-status-select').focus();
                return false;
            }

            return true;
        }

        /**
         * Populate form with code data
         */
        populateForm(code) {
            const codeId = $('#pavc-code-id');
            const codeInput = $('#pavc-code-input');
            const statusSelect = $('#pavc-status-select');
            const messageInput = $('#pavc-message-input');

            if (codeId.length) codeId.val(code.id);
            if (codeInput.length) codeInput.val(code.zip_code);
            if (statusSelect.length) statusSelect.val(code.availability);
            if (messageInput.length) messageInput.val(code.message || '');
        }

        /**
         * Reset form
         */
        resetForm() {
            const form = $('#pavc-code-form')[0];
            if (form) {
                form.reset();
            }

            // Also manually clear the fields as a fallback
            const codeId = $('#pavc-code-id');
            const codeInput = $('#pavc-code-input');
            const statusSelect = $('#pavc-status-select');
            const messageInput = $('#pavc-message-input');

            if (codeId.length) codeId.val('');
            if (codeInput.length) codeInput.val('');
            if (statusSelect.length) statusSelect.val('available');
            if (messageInput.length) messageInput.val('');
        }

        /**
         * Show modal
         */
        showModal() {
            const modal = $('#pavc-code-modal');
            if (modal.length) {
                modal.show();
                const codeInput = $('#pavc-code-input');
                if (codeInput.length) {
                    codeInput.focus();
                }
            }
        }

        /**
         * Close modal
         */
        closeModal() {
            const modal = $('#pavc-code-modal');
            if (modal.length) {
                modal.hide();
            }
            this.resetForm();
            this.editingCodeId = null;
        }

        /**
         * Show loading overlay
         */
        showLoading() {
            const overlay = $('#pavc-loading-overlay');
            overlay.css('opacity', '0').show();
            overlay.animate({ opacity: 1 }, 200);
        }

        /**
         * Hide loading overlay
         */
        hideLoading() {
            const overlay = $('#pavc-loading-overlay');
            overlay.animate({ opacity: 0 }, 200, function () {
                $(this).hide();
            });
        }

        /**
         * Show success message
         */
        showSuccess(message) {
            this.showNotice(message, 'success');
        }

        /**
         * Show error message
         */
        showError(message) {
            this.showNotice(message, 'error');
        }

        /**
         * Show notice
         */
        showNotice(message, type = 'success') {
            const noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
            const notice = $(`
                <div class="notice ${noticeClass} is-dismissible pavc-notice">
                    <p>${this.escapeHtml(message)}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);

            // Remove existing notices
            $('.pavc-notice').remove();

            // Add new notice
            $('.pavc-availability-settings').prepend(notice);

            // Auto dismiss after 5 seconds
            setTimeout(() => {
                notice.fadeOut(() => notice.remove());
            }, 5000);

            // Handle dismiss button
            notice.find('.notice-dismiss').on('click', function () {
                notice.fadeOut(() => notice.remove());
            });
        }



        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /**
         * Debounce function
         */
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function () {
        // Only initialize if we're on the availability settings page
        if ($('#pavc-codes-table').length > 0) {
            window.pavcAdmin = new PAVCAdmin();
        }
    });

})(jQuery);
