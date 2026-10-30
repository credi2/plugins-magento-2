<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Block;

use Magento\Payment\Block\Info as PaymentInfo;

class Info extends PaymentInfo
{
    protected $_template = 'Credi2_CashpressoPayments::info/cashpresso.phtml';

    public function getMethodCode(): string
    {
        return $this->getMethod()->getCode();
    }

    public function getPurchaseId(): ?string
    {
        return $this->getInfo()->getAdditionalInformation('purchase_id');
    }

    public function getAuthorizationId(): ?string
    {
        return $this->getInfo()->getAdditionalInformation('authorization_id');
    }

    public function getOrderId(): ?string
    {
        return $this->getInfo()->getAdditionalInformation('order_id');
    }

    public function getCashpressoOrderNumber(): ?string
    {
        return $this->getInfo()->getAdditionalInformation('cashpresso_order_number');
    }
}
