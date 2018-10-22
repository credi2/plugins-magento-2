<?php

namespace LimeSoda\Cashpresso\Plugin\Config;

use Magento\Config\Model\Config;
use LimeSoda\Cashpresso\Gateway\Config as CashpressoConfig;
use Magento\Config\Model\ResourceModel\Config as SystemConfig;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use LimeSoda\Cashpresso\Helper\Store;
use Magento\Framework\App\Action\Context;
use LimeSoda\Cashpresso\Api\Account;
use LimeSoda\Cashpresso\Model\Ui\ConfigProvider;
use Magento\Framework\Serialize\Serializer\Serialize;

class SavePlugin
{
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;
    protected $appConfig;
    protected $helper;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    protected $context;
    protected $csConfig;

    protected $accountApi;

    protected $subject;

    protected $serializer;

    public function __construct(
        CashpressoConfig $config,
        Context $context,
        SystemConfig $resourceConfig,
        ReinitableConfigInterface $appConfig,
        Store $storeHelper,
        Account $account,
        Serialize $serializer
    )
    {
        $this->csConfig = $config;

        $this->resourceConfig = $resourceConfig;

        $this->appConfig = $appConfig;

        $this->helper = $storeHelper;

        $this->context = $context;

        $this->messageManager = $context->getMessageManager();

        $this->accountApi = $account;

        $this->serializer = $serializer;
    }

    /**
     * @param Config $subject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterSave(Config $subject)
    {
        $this->subject = $subject;

        if ($this->subject->getSection() == 'payment') {
            $this->checkCurrency();
            $this->getTargetAccounts();
        }
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function checkCurrency()
    {
        $cashpressoCurrency = $this->csConfig->getContractCurrency();

        if ($cashpressoCurrency) {
            $currentStoreId = $this->helper->getCurrentStoredId();
            $websiteId = $this->helper->getCurrentWebsiteId();

            $websites = $this->helper->getWebsites();

            $baseCurrencyCode = $this->helper->getStoreCurrency();

            /** @var \Magento\Store\Model\Website\Interceptor $website */
            foreach ($websites as $website) {
                /** @var \Magento\Store\Model\Group\Interceptor $group */
                foreach ($website->getGroups() as $group) {
                    $stores = $group->getStores();

                    /** @var \Magento\Store\Model\Store\Interceptor $store */
                    foreach ($stores as $store) {
                        if ($currentStoreId == $store->getId() || (!$currentStoreId && $websiteId) || (!$currentStoreId && !$websiteId)) {
                            $currency = $this->helper->getStoreCurrency($store->getId());

                            if ($cashpressoCurrency != $currency) {

                                if ($this->csConfig->isActive($store->getId())) {
                                    $this->resourceConfig->saveConfig(
                                        implode('/', ['payment', ConfigProvider::CODE, CashpressoConfig::KEY_ACTIVE]),
                                        0,
                                        'stores',
                                        $store->getId()
                                    );
                                    $this->appConfig->reinit();

                                    $message = __('Currency %1 for the store "%2" could not be used for Cashpresso payment. Use only EUR. Please change the currency settings for your store.', $baseCurrencyCode, $this->helper->getStoreName());
                                    $this->messageManager->addWarningMessage($message);
                                } else {
                                    $message = __('Currency %1 for the store "%2" could not be used for Cashpresso payment. Use only EUR. Please change the currency settings for your store.', $baseCurrencyCode, $this->helper->getStoreName());
                                    $this->messageManager->addNoticeMessage($message);
                                }
                            }
                        }
                    }
                }
            }
        }

        $this->appConfig->reinit();

        if ($this->csConfig->isActive(0)) {

            $saveInactiveStatus = false;

            if (!$this->csConfig->getAPIKey()) {
                $message = __('cashpresso: API key is missing');
                $this->messageManager->addWarningMessage($message);
                $saveInactiveStatus = true;
            }

            if (!$this->csConfig->getSecretKey()) {
                $message = __('cashpresso: Secret key is missing');
                $this->messageManager->addWarningMessage($message);
                $saveInactiveStatus = true;
            }

            if ($saveInactiveStatus) {
                $this->resourceConfig->saveConfig(
                    implode('/', ['payment', ConfigProvider::CODE, CashpressoConfig::KEY_ACTIVE]),
                    0,
                    'default',
                    0
                );
                $this->appConfig->reinit();
            }
        }
    }

    protected function getTargetAccounts()
    {
        if ($this->csConfig->isActive(0) && $this->csConfig->getAPIKey() && $this->csConfig->getSecretKey()) {

            $accountValue = $this->subject->getData('groups/cashpresso/fields/account');

            if (isset($accountValue['value']) && $accountValue['value'] == CashpressoConfig::XML_RELOAD_FLAG) {
                $accounts = $this->accountApi->getTargetAccounts();

                if (is_array($accounts)){
                    $this->resourceConfig->saveConfig(
                        implode('/', ['payment', ConfigProvider::CODE, CashpressoConfig::XML_PARTNER_TARGET_ACCOUNTS]),
                        $this->serializer->serialize($accounts),
                        'default',
                        0
                    );

                    $this->appConfig->reinit();
                }
            }
        }
    }
}