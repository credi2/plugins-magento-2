<?php

namespace LimeSoda\Cashpresso\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class AvailabilityObserver extends AbstractDataAssignObserver
{
    /**
     * @return float
     */
    public function getGrandTotal()
    {
        return $this->checkoutSession->getQuote()->getGrandTotal();
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        /** @var \Magento\Payment\Model\Method\Adapter $methodInstance */
        $methodInstance = $event->getMethodInstance();

        if ($methodInstance instanceof \Magento\Payment\Model\Method\Adapter\Interceptor && $methodInstance->getCode() == 'cashpresso') {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $event->getData('quote');

            if (!$quote instanceof \Magento\Quote\Api\Data\CartInterface) {
                return;
            }

            $totalLimit = $methodInstance->getConfigData('totallimit');
            $quoteTotalLimit = $quote->getGrandTotal();
            $shippingMethod = $quote->getShippingAddress()->getShippingMethod();

            /** @var \Magento\Framework\DataObject $result */
            $result = $observer->getEvent()->getResult();

            if (($quoteTotalLimit > $totalLimit && $quoteTotalLimit) || ($shippingMethod && !$quoteTotalLimit)) {
                $result->setData('is_available', false);
            } else if (!$this->isAllowed($quote)){
                $result->setData('is_available', false);
            }
        }
    }

    private function isAllowed($quote)
    {
        $items = $quote->getItems();

        $status = true;

        /** @var \Magento\Quote\Model\Quote\Item $item */
        if ($items) {
            foreach ($items as $item) {
                if (in_array($item->getProduct()->getTypeId(), ['virtual', 'downloadable', 'giftcard'], true)){
                    $status = false;
                    break;
                }
            }
        }

        return $status;
    }
}
