<?php

namespace LimeSoda\Cashpresso\Plugin\Catalog\ProductList;

class PricePlugin
{
    /**
     * @param \Magento\Catalog\Block\Product\ListProduct $product
     * @param $value
     * @return mixed
     */
    public function afterGetProductPrice(
        \Magento\Framework\DataObject\IdentityInterface $list,
        $price,
        $product
    )
    {
        $priceRender = $list->getLayout()->getBlock('product.price.render.default')
            ->setData('is_product_list', true);

        $priceCS = '';

        if ($priceRender) {
            $priceCS = $priceRender->render(
                'cashpresso_price',
                $product,
                [
                    'include_container' => true,
                    'display_minimal_price' => true,
                    'zone' => \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST,
                    'list_category_page' => true
                ]
            );
        }

        return $price . $priceCS;
    }
}