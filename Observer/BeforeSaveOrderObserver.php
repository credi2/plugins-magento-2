<?php

namespace LimeSoda\Cashpresso\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use LimeSoda\Cashpresso\Api\Checkout;
use Magento\Framework\App\RequestInterface;

class BeforeSaveOrderObserver extends AbstractDataAssignObserver
{

    protected RequestInterface $request;

    protected Checkout $checkout;

    public function __construct(
        RequestInterface $request,
        Checkout $checkout
    )
    {
        $this->request = $request;
        $this->checkout = $checkout;
    }

    /**
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $order = $event->getOrder();

        if ($order->getPayment()->getMethod() === 'cashpresso') {
            $purchaseId = $this->checkout->sendOrder($order);
            $order->getPayment()->setAdditionalInformation('purchaseId', $purchaseId);
        }
    }
}
