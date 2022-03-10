define([
    'jquery',
    'Magento_Checkout/js/model/quote'
], function ($, quote) {
    'use strict';

    return function () {

        var paymentMethod = quote.getPaymentMethod()();

        if (paymentMethod && paymentMethod.method !== 'cashpresso') {
            return false;
        }

        var refreshData = {};

        var email = quote.guestEmail ? quote.guestEmail : window.checkoutConfig.customerData.email;

        if (email) {
            refreshData.email = email;
        }

        var useShippingAddress = $('.payment-method._active input[name=billing-address-same-as-shipping]').is(':checked');

        var firstname = useShippingAddress ? quote.shippingAddress().firstname : quote.billingAddress().firstname;

        if (firstname) {
            refreshData.given = firstname;
        }

        var lastname = useShippingAddress ? quote.shippingAddress().lastname : quote.billingAddress().lastname;

        if (lastname) {
            refreshData.family = lastname;
        }

        var telephone = useShippingAddress ? quote.shippingAddress().telephone : quote.billingAddress().telephone;

        if (telephone) {
            refreshData.phone = lastname;
        }

        var postcode = useShippingAddress ? quote.shippingAddress().postcode : quote.billingAddress().postcode;

        if (postcode) {
            refreshData.zip = postcode;
        }

        var city = useShippingAddress ? quote.shippingAddress().city : quote.billingAddress().city;

        if (city) {
            refreshData.city = city;
        }

        var countryId = useShippingAddress ? quote.shippingAddress().countryId : quote.billingAddress().countryId;

        if (countryId) {
            refreshData.country = countryId;
        }

        var street = useShippingAddress ? quote.shippingAddress().street : quote.billingAddress().street;

        if (street instanceof Array) {
            var address = street.join(', ');

            if (address) {
                refreshData.addressline = address;
            }
        }

        var vatId = useShippingAddress ? quote.shippingAddress().vatId : quote.billingAddress().vatId;

        if (vatId) {
            refreshData.iban = vatId;
        }

        if (paymentMethod && quote.getPaymentMethod && window.checkoutConfig.payment[paymentMethod.method].debug) {
            console.log(refreshData);
            console.log(quote.totals()['base_grand_total']);
        }

        if (window.C2EcomCheckout) {
            window.C2EcomCheckout.refresh();
            $('#c2CheckoutScript').attr('data-c2-amount', quote.totals()['base_grand_total']);
            window.C2EcomCheckout.init();
            window.C2EcomCheckout.refreshOptionalData(refreshData);
            return true;
        }

        return false;
    }
});