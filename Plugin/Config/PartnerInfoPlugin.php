<?php

namespace LimeSoda\Cashpresso\Plugin\Config;

use LimeSoda\Cashpresso\Gateway\Config;
use LimeSoda\Cashpresso\Model\PartnerInfo;

class PartnerInfoPlugin
{
    protected $csConfig;

    protected $partnerInfo;

    public function __construct(Config $config,
                                PartnerInfo $partnerInfo
    )
    {
        $this->csConfig = $config;
        $this->partnerInfo = $partnerInfo;
    }

    public function afterSave(
        \Magento\Config\Model\Config $subject
    )
    {
        if ($subject->getSection() == 'payment' && $this->csConfig->getAPIKey()) {
            $this->partnerInfo->generatePartnerInfo();
        }
    }
}