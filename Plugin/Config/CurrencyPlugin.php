<?php


namespace LimeSoda\Cashpresso\Plugin\Config;

use Magento\Config\Model\Config;
use LimeSoda\Cashpresso\Gateway\Config as CashpressoConfig;
use Magento\Config\Model\ResourceModel\Config as SystemConfig;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use LimeSoda\Cashpresso\Helper\Store;
use Magento\Framework\App\Action\Context;

class CurrencyPlugin
{
    private $csConfig;

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

    public function __construct(
        CashpressoConfig $config,
        Context $context,
        SystemConfig $resourceConfig,
        ReinitableConfigInterface $appConfig,
        Store $storeHelper
    )
    {
        $this->csConfig = $config;

        $this->resourceConfig = $resourceConfig;

        $this->appConfig = $appConfig;

        $this->helper = $storeHelper;

        $this->context = $context;

        $this->messageManager = $context->getMessageManager();
    }

    /**
     * @param Config $subject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterSave(Config $subject)
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
                                        implode('/', ['payment', \LimeSoda\Cashpresso\Model\Ui\ConfigProvider::CODE, CashpressoConfig::KEY_ACTIVE]),
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
    }
}