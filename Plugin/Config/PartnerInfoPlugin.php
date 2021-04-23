<?php

namespace LimeSoda\Cashpresso\Plugin\Config;

use LimeSoda\Cashpresso\Gateway\Config;
use LimeSoda\Cashpresso\Helper\Store;
use LimeSoda\Cashpresso\Model\PartnerInfo;
use Magento\Store\Model\StoreManagerInterface;

class PartnerInfoPlugin
{
    protected $csConfig;

    protected $partnerInfo;

    protected $store;

    public function __construct(Config $config,
                                PartnerInfo $partnerInfo,
                                Store $storeHelper
    )
    {
        $this->csConfig = $config;
        $this->partnerInfo = $partnerInfo;
        $this->store = $storeHelper;
    }

    public function afterSave(
        \Magento\Config\Model\Config $subject
    )
    {
        if ($subject->getSection() == 'payment' && $this->csConfig->getAPIKey($this->store->getCurrentStoredId())) {
            $this->partnerInfo->generatePartnerInfo();
        }
    }
}
