<?php

namespace LimeSoda\Cashpresso\Plugin\Catalog\ProductList\Widget;

class PricePlugin
{
    /**
     * @param \Magento\Catalog\Block\Product\ListProduct $product
     * @param $value
     * @return mixed
     */
    public function afterGetProductPriceHtml(
        \Magento\Widget\Block\BlockInterface $widget,
        $price,
        $product
    )
    {
        $priceRender = null;

        if (!$priceRenderer = $widget->getLayout()->getBlock('product.price.render.default')) {
            return $price;
        }

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