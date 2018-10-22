<?php

namespace LimeSoda\Cashpresso\Helper;

use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\State;
use Magento\Framework\Locale\Resolver;

class Store
{
    protected $request;

    protected $storeManager;

    protected $store;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;

    public function __construct(
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        State $state,
        Resolver $store
    )
    {
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->state = $state;
        $this->store = $store;
    }

    /**
     * @return bool|int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCurrentStoredId()
    {
        return $this->state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML ? (int)$this->request->getParam('store', 0) : true;
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCurrentWebsiteId()
    {
        if ((int)$this->request->getParam('website', 0)) {
            return (int)$this->request->getParam('website');
        }
        $storeId = $this->getCurrentStoredId();
        $store = $this->storeManager->getStore($storeId);
        $websiteId = $store->getWebsiteId();

        return $websiteId;
    }

    /**
     * @param null $storeId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getStoreCurrency($storeId = null)
    {
        $storeId = $storeId ? $storeId : $this->getCurrentStoredId();

        return (string)$this->storeManager->getStore($storeId)->getCurrentCurrency()->getCode();
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getStoreName($storeId = null)
    {
        return (string)$this->storeManager->getStore($storeId)->getName();
    }

    public function getWebsites()
    {
        return $this->storeManager->getWebsites();
    }

    public function getLocale()
    {
        list($code) = explode('_', $this->store->getLocale());

        return $code;
    }
}