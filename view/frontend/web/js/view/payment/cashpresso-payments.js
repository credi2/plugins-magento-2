/* registered in app/code/Credi2/CashpressoPayments/view/frontend/layout/checkout_index_index.xml */

define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push(
        {
            type: 'cashpresso',
            component: 'Credi2_CashpressoPayments/js/view/payment/method/cashpresso'
        },
        {
            type: 'cashpresso_bnpl_installment',
            component: 'Credi2_CashpressoPayments/js/view/payment/method/bnpl-installment'
        },
        {
            type: 'cashpresso_bnpl_invoice',
            component: 'Credi2_CashpressoPayments/js/view/payment/method/bnpl-invoice'
        }
    );

    return Component.extend({});
});
