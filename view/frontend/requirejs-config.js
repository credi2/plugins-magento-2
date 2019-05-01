/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    config: {
        mixins: {
            'Magento_Catalog/js/price-box': {
                'LimeSoda_Cashpresso/js/price-box': true
            },
            'Magento_Checkout/js/view/billing-address': {
                'LimeSoda_Cashpresso/js/view/billing-address': true
            }
        }
    }
};
