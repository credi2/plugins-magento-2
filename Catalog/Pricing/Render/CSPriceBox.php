<?php

namespace LimeSoda\Cashpresso\Catalog\Pricing\Render;

use \Magento\Catalog\Pricing\Render\FinalPriceBox;
use LimeSoda\Cashpresso\Gateway;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Pricing\Render\RendererPool;

class CSPriceBox extends FinalPriceBox
{
    protected $available;

    protected $config;

    /**
     * CSPriceBox constructor.
     *
     * @param Context $context
     * @param SaleableInterface $saleableItem
     * @param PriceInterface $price
     * @param RendererPool $rendererPool
     * @param Gateway\Available $available
     * @param Gateway\Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        SaleableInterface $saleableItem,
        PriceInterface $price,
        RendererPool $rendererPool,
        Gateway\Available $available,
        Gateway\Config $config,
        array $data = []
    )
    {
        $this->config = $config;

        $this->available = $available;

        parent::__construct($context, $saleableItem, $price, $rendererPool, $data);
    }

    /**
     * @return Gateway\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    protected function _toHtml()
    {
        if ($salableItem = $this->getSaleableItem()) {
            if ($salableItem->getTypeId()) {

                if ($this->isProductList()) {
                    $type = \LimeSoda\Cashpresso\Gateway\Available::CATALOG;
                } else {
                    $type = \LimeSoda\Cashpresso\Gateway\Available::PRODUCT;
                }

                if (in_array($salableItem->getTypeId(), ['virtual', 'downloadable', 'giftcard']) || !$this->available->isAvailable($type)) {
                    return '';
                }
            }
        }

        return parent::_toHtml();
    }

    public function getCsFinalPrice()
    {
        $price = 0;

        if ($salableItem = $this->getSaleableItem()) {
            if ($salableItem->getTypeId()) {
                switch ($salableItem->getTypeId()) {
                    case 'configurable':
                        $finalPriceModel = $this->getPriceType('final_price');

                        $price = $finalPriceModel->getAmount()->getValue();
                        break;
                    case 'grouped':
                        $minProduct = $this->getSaleableItem()
                            ->getPriceInfo()
                            ->getPrice(\Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE)
                            ->getMinProduct();

                        $price = $minProduct->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
                        break;
                    case 'bundle':
                        /** @var \Magento\Bundle\Pricing\Price\FinalPrice $finalPriceModel */
                        $finalPriceModel = $this->getPrice();
                        $price = $finalPriceModel->getMinimalPrice()->getValue();
                        break;
                    default:
                        $finalPriceModel = $this->getPriceType('final_price');
                        $price = $finalPriceModel->getAmount()->getValue();
                        break;
                }
            }
        }

        return $price;
    }
}