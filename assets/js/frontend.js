/**
 * Product Availability Checker - Frontend JavaScript
 *
 * Handles the availability checking functionality on product pages
 */

(function ($) {
    'use strict';

    // Main availability checker object
    const PAVC = {
        // DOM elements
        $checker: null,
        $zipInput: null,
        $checkBtn: null,
        $loading: null,
        $result: null,
        $error: null,
        $resultIcon: null,
        $statusText: null,
        $messageText: null,
        $errorMessage: null,

        // State
        isChecking: false,
        productId: null,
        $addToCartBtn: null,
        originalAddToCartText: null,

        /**
         * Initialize the availability checker
         */
        init: function () {
            this.cacheElements();
            this.bindEvents();
            this.productId = this.$checker.data('product-id') || pavcFrontend.productId;

            // Initially disable add to cart until availability is checked
            this.disableAddToCartInitially();
        },

        /**
         * Cache DOM elements
         */
        cacheElements: function () {
            this.$checker = $('#pavc-availability-checker');
            this.$zipInput = $('#pavc-zip-input');
            this.$checkBtn = $('#pavc-check-btn');
            this.$loading = $('#pavc-loading');
            this.$result = $('#pavc-result');
            this.$error = $('#pavc-error');
            this.$resultIcon = $('.pavc-result-icon');
            this.$statusText = $('.pavc-status-text');
            this.$messageText = $('.pavc-message-text');
            this.$errorMessage = $('.pavc-error-message');

            // Cache add to cart button and store original text BEFORE any modifications
            this.$addToCartBtn = $('.single_add_to_cart_button').first();
            if (!this.$addToCartBtn.length) {
                this.$addToCartBtn = $('.add_to_cart_button').first();
            }
        },
        /**
         * Bind event handlers
         */
        bindEvents: function () {
            // Check button click
            this.$checkBtn.on('click', this.handleCheckClick.bind(this));

            // Enter key in zip input
            this.$zipInput.on('keypress', function (e) {
                if (e.which === 13) { // Enter key
                    e.preventDefault();
                    PAVC.handleCheckClick();
                }
            });

            // Input validation on zip code field
            this.$zipInput.on('input', this.handleZipInput.bind(this));

            // Clear results when input changes
            this.$zipInput.on('input', this.clearResults.bind(this));
        },

        /**
         * Handle check button click
         */
        handleCheckClick: function () {
            if (this.isChecking) {
                return;
            }

            const zipCode = this.getZipCode();

            if (!this.validateZipCode(zipCode)) {
                this.showError(pavcFrontend.strings.invalidZipCode);
                return;
            }

            this.checkAvailability(zipCode);
        },

        /**
         * Handle zip code input
         */
        handleZipInput: function () {
            // Allow only alphanumeric characters and spaces/dashes
            let value = this.$zipInput.val();
            value = value.replace(/[^a-zA-Z0-9\s\-]/g, '');
            this.$zipInput.val(value);
        },

        /**
         * Get cleaned zip code
         */
        getZipCode: function () {
            return this.$zipInput.val().trim().toUpperCase();
        },

        /**
         * Validate zip code format
         */
        validateZipCode: function (zipCode) {
            // Basic validation - not empty and reasonable length
            return zipCode.length >= 3 && zipCode.length <= 10;
        },

        /**
         * Clear all result displays
         */
        clearResults: function () {
            this.$result.hide();
            this.$error.hide();

            // Reset add to cart button to initial disabled state when clearing results
            this.disableAddToCartInitially();
        },

        /**
         * Check availability via AJAX
         */
        checkAvailability: function (zipCode) {
            this.setCheckingState(true);
            this.clearResults();

            const data = {
                action: 'pavc_check_availability',
                nonce: pavcFrontend.nonce,
                zip_code: zipCode,
                product_id: this.productId
            };

            $.ajax({
                url: pavcFrontend.ajaxUrl,
                type: 'POST',
                data: data,
                timeout: 10000, // 10 second timeout
                success: this.handleSuccess.bind(this),
                error: this.handleError.bind(this),
                complete: function () {
                    PAVC.setCheckingState(false);
                }
            });
        },

        /**
         * Handle successful AJAX response
         */
        handleSuccess: function (response) {
            if (response.success && response.data) {
                this.showResult(response.data);
            } else {
                const errorMessage = response.data && response.data.message
                    ? response.data.message
                    : pavcFrontend.strings.error;
                this.showError(errorMessage);
            }
        },

        /**
         * Handle AJAX error
         */
        handleError: function (xhr, status, error) {
            console.error('PAVC AJAX Error:', status, error);

            let errorMessage = pavcFrontend.strings.error;

            if (status === 'timeout') {
                errorMessage = 'Request timed out. Please try again.';
            } else if (xhr.status === 0) {
                errorMessage = 'Network error. Please check your connection.';
            }

            this.showError(errorMessage);
        },

        /**
         * Show availability result
         */
        showResult: function (data) {
            const isAvailable = data.availability === 'available';
            const zipCode = data.zip_code || this.getZipCode();

            // Set icon
            this.$resultIcon.html(isAvailable ? '✓' : '⚠️');

            // Set status text
            const statusText = isAvailable
                ? pavcFrontend.strings.available
                : pavcFrontend.strings.unavailable;
            this.$statusText.text(statusText);

            // Set message
            let message = data.message || '';
            if (!message) {
                message = isAvailable
                    ? `Available for delivery in ${zipCode}`
                    : pavcFrontend.strings.productNotAvailable;
            }
            this.$messageText.text(message);

            // Apply styling classes
            this.$result.removeClass('pavc-available pavc-unavailable')
                .addClass(isAvailable ? 'pavc-available' : 'pavc-unavailable');

            // Control add to cart button based on availability
            if (isAvailable) {
                this.enableAddToCart();
            } else {
                this.disableAddToCart();
            }

            // Show result
            this.$result.fadeIn(300);
        },

        /**
         * Show error message
         */
        showError: function (message) {
            this.$errorMessage.text(message);
            this.$error.fadeIn(300);
        },

        /**
         * Set checking state (loading)
         */
        setCheckingState: function (isChecking) {
            this.isChecking = isChecking;

            if (isChecking) {
                this.$checkBtn.prop('disabled', true).text(pavcFrontend.strings.checking);
                this.$loading.fadeIn(200);
            } else {
                this.$checkBtn.prop('disabled', false).text(pavcFrontend.strings.checkAvailability);
                this.$loading.fadeOut(200);
            }
        },

        /**
         * Disable add to cart button for unavailable products
         */
        disableAddToCart: function () {
            if (this.$addToCartBtn.length) {
                this.$addToCartBtn
                    .prop('disabled', true)
                    .addClass('pavc-disabled')
                    .text('Not Available in Your Area');

                // Add click handler to show message instead of adding to cart
                this.$addToCartBtn.off('click.pavc').on('click.pavc', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                });
            }
        },

        /**
         * Enable add to cart button for available products
         */
        enableAddToCart: function () {
            if (this.$addToCartBtn.length) {
                // Use fallback text if original text is corrupted or empty
                const buttonText = this.originalAddToCartText && this.originalAddToCartText.trim()
                    ? this.originalAddToCartText.trim()
                    : 'Add to cart';

                this.$addToCartBtn
                    .prop('disabled', false)
                    .removeClass('pavc-disabled')
                    .text(buttonText);

                // Remove our click handler
                this.$addToCartBtn.off('click.pavc');
            }
        },

        /**
         * Disable add to cart button for unavailable products
         */
        disableAddToCart: function () {
            if (this.$addToCartBtn.length) {
                this.$addToCartBtn
                    .prop('disabled', true)
                    .addClass('pavc-disabled')
                    .text('Not Available in Your Area');

                // Add click handler to show message instead of adding to cart
                this.$addToCartBtn.off('click.pavc').on('click.pavc', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                });
            }
        },

        /**
         * Initially disable add to cart button until availability is checked
         */
        disableAddToCartInitially: function () {
            if (this.$addToCartBtn.length) {
                this.$addToCartBtn
                    .prop('disabled', true)
                    .addClass('pavc-disabled')
                    .text('Check Availability First');

                // Add click handler to show message
                this.$addToCartBtn.off('click.pavc').on('click.pavc', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    // Focus on zip input to encourage checking
                    if (PAVC.$zipInput.length) {
                        PAVC.$zipInput.focus();
                    }

                    return false;
                });
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function () {
        // Only initialize if the checker element exists
        if ($('#pavc-availability-checker').length) {
            PAVC.init();
        }
    });

    // Expose PAVC object globally for debugging/extending
    window.PAVC = PAVC;

})(jQuery);
