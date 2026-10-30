define([
    'Credi2_CashpressoPayments/js/view/payment/method/bnpl-base'
], function (BnplBase) {
    'use strict';

    return BnplBase.extend({
        defaults: {
            template: 'Credi2_CashpressoPayments/payment/bnpl-invoice'
        },

        getCode: function () {
            return 'cashpresso_bnpl_invoice';
        }
    });
});