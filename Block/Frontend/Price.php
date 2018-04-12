<?php


namespace LimeSoda\Cashpresso\Block\Frontend;

use Magento\Framework\View\Element\Template;
use LimeSoda\Cashpresso\Gateway;
use LimeSoda\Cashpresso\Model\CustomerSession;
use Magento\Checkout\Model\Session;
use Magento\Framework\Locale\Resolver;
use Magento\Catalog\Pricing\Price\MinimalPriceCalculatorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class Price extends Template
{
    private $config;

    private $available;

    private $checkoutSession;

    private $customer;

    private $store;

    private $type;

    private $registry;

    /**
     * @var MinimalPriceCalculatorInterface
     */
    private $minimalPriceCalculator;

    private $priceCurrency;

    /**
     * Price constructor.
     * @param Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param Gateway\Config $config
     * @param Gateway\Available $available
     * @param Session $session
     * @param CustomerSession $customer
     * @param Resolver $store
     * @param MinimalPriceCalculatorInterface $minimalPriceCalculator
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\Registry $registry,
        Gateway\Config $config,
        Gateway\Available $available,
        Session $session,
        CustomerSession $customer,
        Resolver $store,
        MinimalPriceCalculatorInterface $minimalPriceCalculator = null,
        PriceCurrencyInterface $priceCurrencyInterface,
        array $data = [])
    {
        parent::__construct($context, $data);

        $this->config = $config;

        $this->available = $available;

        $this->checkoutSession = $session;

        $this->customer = $customer;

        $this->store = $store;

        $this->registry = $registry;

        $this->priceCurrency = $priceCurrencyInterface;

        $this->setTemplate('LimeSoda_Cashpresso::catalog/price.phtml');

        $this->minimalPriceCalculator = $minimalPriceCalculator
            ?: ObjectManager::getInstance()->get(MinimalPriceCalculatorInterface::class);
    }

    /**
     * Returns Validator pool
     *
     * @return ValidatorPoolInterface
     * @throws \DomainException
     */
    public function getValidatorPool()
    {
        if ($this->validatorPool === null) {
            throw new \DomainException('Validator pool is not configured for use.');
        }
        return $this->validatorPool;
    }

    /**
     * @return \LimeSoda\Cashpresso\Block\Pricing\Render\Amount
     */
    protected function _getAmountBlock()
    {
        return $this->getAmountBlock();
    }

    /**
     * @return \Magento\Framework\Pricing\SaleableInterface
     */
    public function getProduct()
    {
        return $this->_getAmountBlock()->getSaleableItem();
    }

    /**
     * @return \Magento\Framework\Pricing\Render\RendererPool
     */
    public function getRendererPool()
    {
        return $this->_getAmountBlock()->getData('rendererPool');
    }


    /**
     * @return Gateway\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    public function setPageType($type)
    {
        $this->type = $type;
    }

    public function getPrice()
    {
        /** @var \Magento\Framework\Pricing\Render\AmountRenderInterface $product */
        $product = $this->getProduct();
        return $product->getPrice()->getValue();
    }

    public function getProductType()
    {
        /** @var \Magento\Framework\Pricing\Render\AmountRenderInterface $product */
        $product = $this->getProduct();
        return $product ? $product->getTypeId() : true;
    }

    protected function _toHtml()
    {
        if ($this->getCurrentProduct()) {
            $type = \LimeSoda\Cashpresso\Gateway\Available::PRODUCT;
        } else {
            $type = \LimeSoda\Cashpresso\Gateway\Available::CATALOG;
        }

        if (in_array($this->getProductType(), ['virtual', 'downloadable', 'giftcard']) || !$this->available->isAvailable($type)) {
            return '';
        }


        return parent::_toHtml();
    }

    public function getFinalPrice()
    {
        if ($this->getProduct()->getTypeId() == 'bundle') {
            $price = $this->_getAmountBlock()->getAmount()->getValue();
        } else if ($this->getProduct()->getTypeId() == 'configurable') {
            $price = $this->getProduct()->getPriceInfo()->getPrice('final_price')->getValue();
            //$price = $this->minimalPriceCalculator->getAmount($this->getProduct());
        } else if ($this->getProduct()->getTypeId() == 'grouped') {

        } else {
            $price = $this->_getAmountBlock()->getAmount()->getValue();
            //$price = $this->getProduct()->getFinalPrice();
        }

        return $this->priceCurrency->round($price);
    }

    public function getCurrentCategory()
    {
        return $this->registry->registry('current_category');
    }

    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }
}