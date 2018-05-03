<?php


namespace LimeSoda\Cashpresso\Block\Frontend;

use Magento\Framework\View\Element\Template;
use LimeSoda\Cashpresso\Gateway;
use LimeSoda\Cashpresso\Model\CustomerSession;
use Magento\Checkout\Model\Session;
use LimeSoda\Cashpresso\Helper\Store;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;

class Script extends Template
{
    private $config;

    private $available;

    private $checkoutSession;

    private $customer;

    private $store;

    private $type;

    private $priceCurrency;

    private $registry;

    /**
     * Script constructor.
     * @param Template\Context $context
     * @param \LimeSoda\Cashpresso\Gateway\Config $config
     * @param \LimeSoda\Cashpresso\Gateway\Available $available
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Gateway\Config $config,
        Gateway\Available $available,
        Session $session,
        CustomerSession $customer,
        Store $store,
        PriceCurrencyInterface $priceCurrencyInterface,
        Registry $registry,
        array $data = [])
    {
        $this->config = $config;

        $this->available = $available;

        $this->checkoutSession = $session;

        $this->customer = $customer;

        $this->store = $store;

        $this->priceCurrency = $priceCurrencyInterface;

        $this->registry = $registry;

        parent::__construct($context, $data);
    }

    public function getLocale()
    {
        return $this->store->getLocale();
    }

    /**
     * @return float
     */
    public function getGrandTotal()
    {
        return $this->priceCurrency->round($this->checkoutSession->getQuote()->getGrandTotal());
    }

    /**
     * @return Gateway\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return \Magento\Framework\DataObject
     */
    public function getCustomer()
    {
        return $this->customer->getCustomerData();
    }

    public function setPageType($type)
    {
        $this->type = $type;
    }

    /**
     * @return Product
     */
    private function getProduct()
    {
        $product = $this->registry->registry('product');

        return $product && $product->getId() ? true : false;
    }

    protected function _toHtml()
    {
        if (!$this->available->isAvailable($this->type)){
            return '';
        }

        return parent::_toHtml();
    }

    public function getCheckoutCallbackStatus()
    {
        return $this->config->showCheckoutButton() && $this->getProduct();
    }
}