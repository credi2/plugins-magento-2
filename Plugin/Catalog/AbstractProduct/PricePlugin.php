<?php

namespace LimeSoda\Cashpresso\Plugin\Catalog\AbstractProduct;

class PricePlugin
{
    /**
     * @param \Magento\Catalog\Block\Product\AbstractProduct $productType
     * @param $value
     * @return mixed
     */
    public function afterGetProductPriceHtml(
        \Magento\Catalog\Block\Product\AbstractProduct $productType,
        $price,
        $product
    )
    {
        $priceRender = $productType->getLayout()->getBlock('product.price.render.default');

        $priceCS = '';

        if ($priceRender) {
            $priceCS = $priceRender->render(
                'cashpresso_price',
                $product,
                [
                    'include_container' => false,
                    'display_minimal_price' => true,
                    'zone' => \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST,
                    'list_category_page' => true
                ]
            );
        }

        return $price . $priceCS;
    }
}