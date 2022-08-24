<?php

namespace LimeSoda\Cashpresso\Cron;

use LimeSoda\Cashpresso\Model\PartnerInfo as PartnerInfoModel;

class PartnerInfo
{
    protected PartnerInfoModel $partnerInfo;

    public function __construct(
        PartnerInfoModel $partnerInfo
    ) {
        $this->partnerInfo = $partnerInfo;
    }

    public function execute()
    {
        $result = false;
        $this->partnerInfo->generatePartnerInfo();
        return $result;
    }
}
