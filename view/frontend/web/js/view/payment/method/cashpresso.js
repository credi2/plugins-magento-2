define([
    'Magento_Checkout/js/view/payment/default',
    'mage/url',
    'jquery',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/set-payment-information',
    'Magento_Checkout/js/model/quote',
    'ko',
    'Magento_Checkout/js/action/redirect-on-success',
], function (Component, urlBuilder, $, additionalValidators, setPaymentInformation, quote, ko, redirectOnSuccessAction) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Credi2_CashpressoPayments/payment/cashpresso',
            cashpressoToken: ko.observable(''),
            selectedPlan: null,
            plansLoaded: false
        },

        initialize: function () {
            this._super();
            this.loadJS();
            return this;
        },

        getCode: function () {
            return 'cashpresso';
        },

        isActive: function () {
            return true;
        },

        hasToken: function () {
            return document.getElementById('cashpressoToken').value !== '';
        },
        /**
         * Load cashpresso external JS library
         */
        loadJS: function () {
            const config = window.checkoutConfig.payment[this.getCode()];
            if (!config) {
                return;
            }

            // Setup global variables and callbacks
            window.isCashpressoPlanSelected = false;
            const self = this;

            window.c2Checkout = function (token) {
                window.isCashpressoPlanSelected = true;
                if (token) {
                    self.cashpressoToken(token);
                    $('#cashpresso_token').val(token);
                    $('.cashpresso-buy-button').show();
                }
            };

            $(document).on('click', '#cashpresso-checkout .c2-btn-primary', function (e) {
                e.preventDefault();
                window.C2EcomCheckout.startOverlayWizard();

            });

            $(document).on('click', '#c2-checkout-btn-OK, #c2-checkout-btn-LOGOUT', function (e) {
                e.preventDefault();
                window.C2EcomCheckout.startOverlayWizard();

            });

            // Load script if not already loaded
            if (!document.getElementById('c2CheckoutScript')) {
                const script = document.createElement('script');
                script.id = 'c2CheckoutScript';
                script.type = 'text/javascript';
                script.src = 'https://my.cashpresso.com/ecommerce/v2/checkout/c2_ecom_checkout.all.min.js';

                // Set cashpresso script attributes based on config
                script.setAttribute('data-c2-partnerApiKey', config.apiKey);
                script.setAttribute('data-c2-interestFreeDaysMerchant', config.interestFreeDays);
                script.setAttribute('data-c2-mode', config.isLive ? 'live' : 'test');
                script.setAttribute('data-c2-amount', quote.totals().grand_total);
                script.setAttribute('data-c2-locale', config.locale);
                script.setAttribute('data-c2-checkoutCallback', 'true');

                // Add customer data if available
                if (config.customerData) {
                    const customer = config.customerData;
                    if (customer.birthday) {
                        script.setAttribute('data-c2-birthdate', customer.birthday);
                    }
                    if (customer.email) {
                        script.setAttribute('data-c2-email', customer.email);
                    }
                    if (customer.firstName) {
                        script.setAttribute('data-c2-given', customer.firstName);
                    }
                    if (customer.lastName) {
                        script.setAttribute('data-c2-family', customer.lastName);
                    }
                    if (customer.country) {
                        script.setAttribute('data-c2-country', customer.country);
                    }
                    if (customer.city) {
                        script.setAttribute('data-c2-city', customer.city);
                    }
                    if (customer.zipcode) {
                        script.setAttribute('data-c2-zip', customer.zipcode);
                    }
                    if (customer.street) {
                        script.setAttribute('data-c2-addressline', customer.street);
                    }
                    if (customer.phoneNumber) {
                        script.setAttribute('data-c2-phone', customer.phoneNumber);
                    }
                }

                document.head.appendChild(script);
            }


        },

        originalPlaceOrder() {
            const self = this;

            this.getPlaceOrderDeferredObject()
                .done(
                    function () {
                        self.afterPlaceOrder();

                        if (self.redirectAfterPlaceOrder) {
                            redirectOnSuccessAction.execute();
                        }
                    }
                ).always(
                function () {
                    self.isPlaceOrderActionAllowed(true);
                }
            );

            return true;

        },


        /**
         * Place order
         */
        placeOrder: function (data, event) {
            const self = this;

            if (event) {
                event.preventDefault();
            }

            if (additionalValidators.validate()) {
                const token = $('#cashpressoToken').val();

                if (!token) {
                    this.messageContainer.addErrorMessage({
                        message: 'Please select a payment plan.'
                    });
                    return false;
                }

                this.isPlaceOrderActionAllowed(false);

                $.ajax({
                    url: urlBuilder.build('cashpresso/cashpresso/settoken'),
                    type: 'POST',
                    data: {
                        cashpresso_token: token
                    },
                    showLoader: true,
                    success: function (response) {
                        if (response.success) {
                            self.originalPlaceOrder();
                        } else {
                            self.messageContainer.addErrorMessage({
                                message: response.message || 'Failed to save the selected payment plan.'
                            });
                            self.isPlaceOrderActionAllowed(true);
                        }
                    },
                    error: function () {
                        self.messageContainer.addErrorMessage({
                            message: 'Payment initialization failed. Please try again.'
                        });
                        self.isPlaceOrderActionAllowed(true);
                    }
                });


                return true;
            }

            return false;
        },
    });
});
