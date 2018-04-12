define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'underscore',
    'mage/template',
    'Magento_Catalog/js/price-box',
    'jquery/ui'
], function($, utils, _, mageTemplate){

    $.widget('LimeSoda_Cashpresso.priceBox', $.mage.priceBox, {
        /**
         * Render price unit block.
         */
        reloadPrice: function reDrawPrices() {

            var priceFormat = (this.options.priceConfig && this.options.priceConfig.priceFormat) || {},
                priceTemplate = mageTemplate(this.options.priceTemplate);

            _.each(this.cache.displayPrices, function (price, priceCode) {
                price.final = _.reduce(price.adjustments, function (memo, amount) {
                    return memo + amount;
                }, price.amount);

                price.formatted = utils.formatPrice(price.final, priceFormat);

                // \/ cashpresso
                if (priceCode == 'finalPrice' && typeof C2EcomWizard !== 'undefined') {
                    var cs_price = price.final;

                    if (typeof C2EcomWizard.ls_status === "function") { // Product level

                        var C2link = document.getElementById('c2-financing-label-' + this.options.productId);

                        if (typeof C2link !== 'undefined') {
                            if (cs_price > 0) {
                                C2EcomWizard.refreshAmount('c2-financing-label-' + this.options.productId, cs_price);
                            }

                            var C2link = document.getElementById('c2-financing-label-' + this.options.productId);

                            if (typeof C2EcomWizard.ls_status !== 'undefined') {
                                C2EcomWizard.ls_status(C2link, cs_price);
                            }
                        } else {
                            console.log('c2-financing-label-' + this.options.productId + ' not found');
                        }
                    } else if (typeof C2EcomWizard.ls_template === "function") {
                        if (document.getElementById('cashpresso_product_id_' + this.options.productId)) {
                            var C2link = document.getElementById('cashpresso_product_id_' + this.options.productId);

                            C2link.onclick = function () {
                                C2EcomWizard.startOverlayWizard(cs_price)
                            }

                            if (typeof C2EcomWizard.ls_template !== 'undefined') {
                                C2link.innerHTML = C2EcomWizard.ls_template(C2link, cs_price);
                            }
                        } else {
                            console.log('c2-financing-label-' + this.options.productId + ' not found');
                        }
                    }
                }
                // /\ cashpresso

                $('[data-price-type="' + priceCode + '"]', this.element).html(priceTemplate({
                    data: price
                }));
            }, this);
        }
    });

    return $.LimeSoda_Cashpresso.priceBox;
});
