<?php

namespace LimeSoda\Cashpresso\Gateway;

use DomainException;
use LimeSoda\Cashpresso\Helper\Store;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Framework\Registry;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote\Item;

class Available
{
    const CHECKOUT = 'checkout';
    const PRODUCT = 'product';
    const CATALOG = 'catalog';

    protected $config;

    /**
     * @var ValidatorPoolInterface
     */
    protected ?ValidatorPoolInterface $validatorPool;

    protected Registry $registry;

    protected Session $session;

    protected Store $store;

    public function __construct(
        Config $config,
        Registry $registry,
        Session $session,
        Store $storeHelper,
        ValidatorPoolInterface $validatorPool = null
    )
    {
        $this->registry = $registry;
        $this->validatorPool = $validatorPool;
        $this->config = $config;
        $this->session = $session;
        $this->store = $storeHelper;
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
     * @throws DomainException
     */
    public function getValidatorPool()
    {
        if ($this->validatorPool === null) {
            throw new DomainException('Validator pool is not configured for use.');
        }
        return $this->validatorPool;
    }

    /**
     * @return bool
     */
    protected function isProductTypeAllow()
    {
        if ($product = $this->getProduct()) {
            return !in_array($product->getTypeId(), ['virtual', 'downloadable', 'giftcard'], true);
        }

        return true;
    }

    /**
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function checkCartItems(): bool
    {
        $items = $this->session->getQuote()->getItems();
        $status = true;

        /** @var Item $item */
        foreach ($items as $item) {
            if (in_array($item->getProduct()->getTypeId(), ['virtual', 'downloadable', 'giftcard'], true)){
                $status = false;
                break;
            }
        }

        return $status;
    }

    /**
     * @param $type
     * @return array|false|mixed|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
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
