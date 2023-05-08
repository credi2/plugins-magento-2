<?php

namespace LimeSoda\Cashpresso\Plugin\Config;

use LimeSoda\Cashpresso\Gateway\Config;
use LimeSoda\Cashpresso\Helper\Store;
use LimeSoda\Cashpresso\Model\PartnerInfo;

class PartnerInfoPlugin
{
    const DATA_PATH_PARTNERINFO = 'groups/cashpresso/fields/partnerinfo/inherit';

    protected Config $csConfig;

    protected PartnerInfo $partnerInfo;

    protected Store $store;

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
        if ($subject->getSection() == 'payment' && $this->csConfig->getAPIKey()) {
            $scope = $subject->getScope();
            $scopeId = $subject->getScopeId();
            $partnerInfoInherit = $subject->getDataByPath(self::DATA_PATH_PARTNERINFO);
            if($partnerInfoInherit){
                $this->partnerInfo->removePartnerInfo($scopeId, $scope);
            }else{
                $this->partnerInfo->generatePartnerInfo($scopeId, $scope);
            }
        }
    }
}
