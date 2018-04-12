<?php


namespace LimeSoda\Cashpresso\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;

class BeforeSaveOrderObserver extends AbstractDataAssignObserver
{

    protected $request;

    protected $paymentInterface;

    protected $checkout;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Quote\Api\Data\PaymentInterface $paymentInterface,
        \LimeSoda\Cashpresso\Api\Checkout $checkout
    )
    {
        $this->request = $request;

        $this->paymentInterface = $paymentInterface;

        $this->checkout = $checkout;
    }

    /**
     * @param Observer $observer
     * @throws \LimeSoda\Cashpresso\Gateway\Exception
     * @throws \Zend_Http_Client_Exception
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $order = $event->getOrder();

        $purchaseId = $this->checkout->sendOrder($order);

        $order->getPayment()->setAdditionalInformation('purchaseId', $purchaseId);
    }
}