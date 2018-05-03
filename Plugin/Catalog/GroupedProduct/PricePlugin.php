<?php

namespace LimeSoda\Cashpresso\Plugin\Catalog\GroupedProduct;

class PricePlugin
{
    /**
     * @param \Magento\Catalog\Block\Product\ListProduct $product
     * @param $value
     * @return mixed
     */
    public function afterGetProductPrice(
        \Magento\GroupedProduct\Block\Product\View\Type\Grouped $block,
        $price,
        $product
    )
    {
        $priceCS = $block->getProductPriceHtml($product,
            'cashpresso_price',
            \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST
        );

        return $price . $priceCS;
    }
}