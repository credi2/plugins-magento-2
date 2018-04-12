<?php


namespace LimeSoda\Cashpresso\Gateway;

use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Framework\Registry;
use Magento\Checkout\Model\Session;

class Available
{
    const CHECKOUT = 'checkout';
    const PRODUCT = 'product';
    const CATALOG = 'catalog';

    private $config;

    /**
     * @var ValidatorPoolInterface
     */
    private $validatorPool;

    private $registry;

    private $session;

    public function __construct(
        Config $config,
        Registry $registry,
        Session $session,
        ValidatorPoolInterface $validatorPool = null
    )
    {
        $this->registry = $registry;

        $this->validatorPool = $validatorPool;

        $this->config = $config;

        $this->session = $session;
    }

    /**
     * @return Product
     */
    private function getProduct()
    {
        $product = $this->registry->registry('product');

        return $product && $product->getId() ? $product : null;
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

    protected function isProductTypeAllow()
    {
        if ($product = $this->getProduct()) {
            return in_array($product->getTypeId(), ['virtual', 'downloadable', 'giftcard']) ? false : true;
        }

        return true;
    }

    protected function checkCartItems()
    {
        $items = $this->session->getQuote()->getItems();

        $status = true;

        /** @var \‌Magento\Quote\Model\Quote\Item $item */
        foreach ($items as $item) {
            $status = in_array($item->getProduct()->getTypeId(), ['virtual', 'downloadable', 'giftcard']) ? false : true;

            if (!$status){
                break;
            }
        }

        return $status;
    }

    public function isAvailable($type = null)
    {
        if (!$this->config->isActive() || !$this->config->getAPIKey() || !$this->config->getSecretKey()) {
            return false;
        }

        $checkResult = new \Magento\Framework\DataObject();
        $checkResult->setData('is_available', true);

        switch ($type) {
            case Available::CHECKOUT:
                $checkResult->setData('is_available', $this->checkCartItems());
                break;
            case Available::PRODUCT:
                $checkResult->setData('is_available', $this->config->checkStatus() && in_array($this->config->getPlaceToShow(), [2, 3]) && $this->isProductTypeAllow());
                break;
            case Available::CATALOG:
                $checkResult->setData('is_available', $this->config->checkStatus() && in_array($this->config->getPlaceToShow(), [1, 3]) && $this->isProductTypeAllow());
                break;
        }

        return $checkResult->getData('is_available');
    }



    /**
     * @todo move to model
     * @param null $storeId
     * @return mixed
     */
    /* public function getProductTypes($storeId = null)
     {
         return Mage::app()->getConfig()->getNode(self::XML_CASHPRESSO_PRODUCT_TYPES)->asArray();
     }*/
}