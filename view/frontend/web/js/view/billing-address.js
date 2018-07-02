define([
    'jquery',
    'LimeSoda_Cashpresso/js/view/payment/update-data'
], function ($, updateData) {
    'use strict';

    return function (Component) {
        return Component.extend({
            updateAddresses: function () {
                this._super();

                return updateData();
            }
        });
    }
});