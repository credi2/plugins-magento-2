/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* @api */
define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/select-payment-method',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, Component, quote, selectPaymentMethodAction, alert) {
    'use strict';


    return Component.extend({
        defaults: {
            template: 'LimeSoda_Cashpresso/payment/cashpresso',
            cashpressoToken: false
        },

        /**
         * Show alert message
         * @param {String} message
         */
        error: function (message) {
            alert({
                content: message
            });
        },

        getCashpressoToken: function () {
            if (typeof document.getElementById("cashpressoToken") !== 'undefined') {
                return document.getElementById("cashpressoToken").value;
            }

            return false;
        },

        getData: function () {
            return {
                'method': this.item.method,
                'additional_data': {
                    'cashpressoToken': this.getCashpressoToken()
                }
            };
        },

        validate: function () {
            if (!this.getData().additional_data.cashpressoToken) {
                this.error($.mage.__('cashpresso: please fill all data.'));
            }

            return true;
        },

        /**
         * Returns payment method instructions.
         *
         * @return {*}
         */
        getInstructions: function () {
            return window.checkoutConfig.payment.instructions[this.item.method];
        },

        /** Returns is method available */
        isAvailable: function () {
            return quote.totals()['grand_total'] < window.checkoutConfig.payment[this.item.method].credit_limit;
        },

        isDebug: function () {
            return window.checkoutConfig.payment[this.item.method].debug;
        },

        /**
         * @return {Boolean}
         */
        getC2EcomCheckout: function () {
            var refreshData = {};

            var email = quote.guestEmail ? quote.guestEmail : window.checkoutConfig.customerData.email;

            if (email) {
                refreshData.email = email;
            }

            var firstname = quote.shippingAddress().firstname;

            if (firstname) {
                refreshData.given = firstname;
            }

            var lastname = quote.shippingAddress().lastname;

            if (lastname) {
                refreshData.family = lastname;
            }

            var telephone = quote.shippingAddress().telephone;

            if (telephone) {
                refreshData.phone = lastname;
            }

            var postcode = quote.shippingAddress().postcode;

            if (postcode) {
                refreshData.zip = postcode;
            }

            var city = quote.shippingAddress().city;

            if (city) {
                refreshData.city = city;
            }

            var countryId = quote.shippingAddress().countryId;

            if (countryId) {
                refreshData.country = countryId;
            }

            if (quote.shippingAddress().street instanceof Array) {
                var address = quote.shippingAddress().street.join(', ');

                if (address) {
                    refreshData.addressline = address;
                }
            }

            /*var dob = window.checkoutConfig.customerData.dob;

            if (dob) {
                refreshData.birthdate = dob;
            }*/

            var vatId = quote.shippingAddress().vatId;

            if (vatId) {
                refreshData.iban = vatId;
            }

            if (this.isDebug()){
                console.log(refreshData);
            }

            if (window.C2EcomCheckout) {
                window.C2EcomCheckout.refresh();
                window.C2EcomCheckout.refreshOptionalData(refreshData);
                console.log(quote.totals()['grand_total']);
                $('#c2CheckoutScript').attr('data-c2-amount', quote.totals()['grand_total']);
                window.C2EcomCheckout.init();
                return true;
            }

            return false;
        }
    });
});