define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Ui/js/model/messageList',
    'mage/url',
    'Magento_Checkout/js/action/redirect-on-success'
], function ($, Component, quote, additionalValidators, messageList, urlBuilder, redirectOnSuccessAction) {
    'use strict';

    return Component.extend({

        initialize: function () {
            this._super();
            this.loadBNPLScript();
            return this;
        },

        isActive: function () {
            return true;
        },

        /**
         * Get formatted total amount
         */
        getFormattedAmount: function () {
            let amount = quote.totals().grand_total;
            if (amount > 0) {
                return '€' + amount.toFixed(2);
            }
            return '€0.00';
        },

        /**
         * Load BNPL wizard script dynamically
         */
        loadBNPLScript: function () {
            const config = window.checkoutConfig.payment[this.getCode()];
            if (!config) {
                return;
            }

            const scriptUrl = config.isLive
                ? 'https://www.purchase.bnpl.cashpresso.com/js/bnpl-wizard.min.js'
                : 'https://www.bnpl-checkout-dialog.test-cashpresso.com/js/bnpl-wizard.min.js';

            // Load script if not already loaded
            if (!window.BNPLWizard && !document.getElementById('bnpl-wizard-script')) {
                const script = document.createElement('script');
                script.id = 'bnpl-wizard-script';
                script.src = scriptUrl;
                script.async = true;
                document.head.appendChild(script);
            }
        },

        /**
         * Place order - initialize BNPL wizard instead of normal order placement
         */
        placeOrder: function (data, event) {
            if (event) {
                event.preventDefault();
            }

            if (!additionalValidators.validate()) {
                return false;
            }

            this.isPlaceOrderActionAllowed(false);

            // Start BNPL wizard flow
            this.initializeBNPLWizard();

            return true;
        },

        /**
         * Initialize BNPL wizard with order data
         */
        initializeBNPLWizard: function () {
            const self = this;

            // First initialize BNPL checkout on backend
            $.ajax({
                url: urlBuilder.build('cashpresso/bnpl/initialize'),
                type: 'POST',
                data: {
                    payment_method: this.getCode()
                },
                showLoader: true,
                success: function (response) {
                    if (response.success) {
                        self.startBNPLWizard(response.authorizationId, response.clientToken);
                    } else {
                        messageList.addErrorMessage({
                            message: response.message || 'Failed to initialize BNPL payment.'
                        });
                        self.isPlaceOrderActionAllowed(true);
                    }
                },
                error: function () {
                    messageList.addErrorMessage({
                        message: 'Payment initialization failed. Please try again.'
                    });
                    self.isPlaceOrderActionAllowed(true);
                }
            });
        },

        /**
         * Start BNPL wizard with authorization data
         */
        startBNPLWizard: function (authorizationId, clientToken) {
            const self = this;
            const config = window.checkoutConfig.payment[this.getCode()];

            // Store authorization data for completion
            this.authorizationId = authorizationId;
            this.clientToken = clientToken;

            // Create and configure script element with attributes
            let wizardScript = document.getElementById('bnpl-wizard');
            if (wizardScript) {
                wizardScript.remove();
            }
            wizardScript = document.createElement('script');
            wizardScript.id = 'bnpl-wizard';
            wizardScript.src = config.scriptUrl;

            // Set BNPL wizard attributes
            wizardScript.setAttribute('bnpl-clienttoken', clientToken);
            wizardScript.setAttribute('bnpl-authorizationid', authorizationId);
            wizardScript.setAttribute('bnpl-mode', config.mode);
            wizardScript.setAttribute('bnpl-locale', config.locale);

            wizardScript.onload = function () {
                try {
                    // Initialize BNPL Wizard
                    window.BNPLWizard.init();
                    window.BNPLWizard.startOverlayWizard();
                } catch (error) {
                    console.error('BNPL Wizard initialization error:', error);
                    messageList.addErrorMessage({
                        message: 'Payment initialization failed. Please try again.'
                    });
                    self.isPlaceOrderActionAllowed(true);
                }
            };

            document.head.appendChild(wizardScript);

            // Setup message listener before initializing wizard
            this.setupMessageListener();
        },

        /**
         * Setup message listener for BNPL wizard callbacks
         */
        setupMessageListener: function () {
            const self = this;

            // Remove existing listener to prevent duplicates
            if (this.messageListener) {
                window.removeEventListener("message", this.messageListener);
            }

            this.messageListener = function (callBackObj) {
                console.log('BNPL Wizard Callback:', callBackObj);

                const callBackData = callBackObj.data;
                const status = callBackData.status;

                if (status && status === 'success') {
                    // Payment successful - complete the order
                    self.completeOrder(callBackData);
                } else {
                    // Payment failed or cancelled
                    self.handlePaymentFailure(callBackData);
                }
            };

            window.addEventListener("message", this.messageListener);
        },

        /**
         * Complete order using Magento's default place order process
         */
        completeOrder: function () {
            const self = this;
            this.isPlaceOrderActionAllowed(false);

            this.getPlaceOrderDeferredObject()
                .done(function () {

                    self.afterPlaceOrder();

                    if (self.redirectAfterPlaceOrder) {
                        redirectOnSuccessAction.execute();
                    }
                })
                .always(function () {
                    self.isPlaceOrderActionAllowed(true);
                });

            return true;
        },

        /**
         * Handle payment failure or cancellation
         */
        handlePaymentFailure: function (errorData) {
            console.error('BNPL Payment failed:', errorData);

            messageList.addErrorMessage({
                message: errorData.message || 'Payment was cancelled or failed. Please try again.'
            });

            this.isPlaceOrderActionAllowed(true);
        }
    });
});
