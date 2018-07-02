/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            priceBox:'LimeSoda_Cashpresso/js/price-box'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/view/billing-address': {
                'LimeSoda_Cashpresso/js/view/billing-address': true
            }
        }
    }
};