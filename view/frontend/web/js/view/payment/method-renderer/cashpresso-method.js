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
    'LimeSoda_Cashpresso/js/view/payment/update-data',
    'mage/translate'
], function ($, Component, quote, selectPaymentMethodAction, alert, updateData) {
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
            if (document.getElementById("cashpressoToken") && typeof document.getElementById("cashpressoToken") !== 'undefined') {
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

        /**
         * @return {Boolean}
         */
        getC2EcomCheckout: function () {
            return updateData();
        }
    });
});