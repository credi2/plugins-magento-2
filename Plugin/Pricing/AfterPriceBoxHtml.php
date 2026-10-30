<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Plugin\Pricing;

use Credi2\CashpressoPayments\Block\Product\Widget;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Pricing\Render\PriceBox;

class AfterPriceBoxHtml
{
    public function __construct(
        private readonly RequestInterface $request
    ) {}

    public function afterToHtml(PriceBox $subject, string $result): string
    {
        $zone = (string)$subject->getData('zone');
        if ($zone !== 'item_list') {
            return $result;
        }

        $fullAction = $this->request->getFullActionName();
        if ($fullAction !== 'catalog_category_view' && $fullAction !== 'catalogsearch_result_index') {
            return $result;
        }

        $item = $subject->getSaleableItem();
        if (!$item instanceof Product) {
            return $result;
        }

        /** @var Widget $widget */
        $widget = $subject->getLayout()->createBlock(Widget::class);
        if (!$widget) {
            return $result;
        }

        $widget->setData('product', $item);

        if (!$widget->isEnabled()) {
            return $result;
        }

        return $result . $widget->toHtml();
    }
}