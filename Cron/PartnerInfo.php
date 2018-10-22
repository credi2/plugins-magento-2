<?php

namespace LimeSoda\Cashpresso\Cron;

class PartnerInfo
{
    protected $partnerInfo;

    public function __construct(
        \LimeSoda\Cashpresso\Model\PartnerInfo $partnerInfo
    )
    {
        $this->partnerInfo = $partnerInfo;
    }

    public function execute()
    {
        $result = false;

        $this->partnerInfo->generatePartnerInfo();

        return $result;
    }
}