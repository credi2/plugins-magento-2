<?php



namespace LimeSoda\Cashpresso\Block\Pricing\Render;

use Magento\Framework\Pricing\Amount\AmountInterface;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Pricing\Render\RendererPool;

/**
 * Price amount renderer
 *
 * @method string getAdjustmentCssClasses()
 * @method string getDisplayLabel()
 * @method string getPriceId()
 * @method bool getIncludeContainer()
 * @method bool getSkipAdjustments()
 */
class Amount extends \Magento\Framework\Pricing\Render\Amount
{
    private $priceBlock;

    /**
     * @param Template\Context $context
     * @param AmountInterface $amount
     * @param PriceCurrencyInterface $priceCurrency
     * @param RendererPool $rendererPool
     * @param SaleableInterface $saleableItem
     * @param \Magento\Framework\Pricing\Price\PriceInterface $price
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        AmountInterface $amount,
        PriceCurrencyInterface $priceCurrency,
        RendererPool $rendererPool,
        SaleableInterface $saleableItem = null,
        PriceInterface $price = null,
        \LimeSoda\Cashpresso\Block\Frontend\Price $priceBlock,
        array $data = []
    ) {
        parent::__construct($context, $amount, $priceCurrency, $rendererPool, $saleableItem, $price, $data);

        $this->priceBlock = $priceBlock;
    }

    protected function _toHtml()
    {
        $html = parent::_toHtml();

        $this->priceBlock->setAmountBlock($this);

        $priceTypes = array('finalPrice', 'minPrice', null);

        return $html . (in_array($this->getPriceType(), $priceTypes) ? $this->priceBlock->toHtml() : '');
    }
}
