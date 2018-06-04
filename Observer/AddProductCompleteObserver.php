<?php


namespace LimeSoda\Cashpresso\Observer;

use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Framework\Event\Observer;

class AddProductCompleteObserver extends AbstractDataAssignObserver
{
    protected $checkout;

    protected $config;

    protected $url;

    protected $responseFactory;

    protected $registry;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    public function __construct(
        \LimeSoda\Cashpresso\Gateway\Config $config,
        \Magento\Framework\Registry $registry
    )
    {
        $this->config = $config;

        $this->registry = $registry;
    }

    /**
     * @param Observer $observer
     * @throws \LimeSoda\Cashpresso\Gateway\Exception
     * @throws \Zend_Http_Client_Exception
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        /** @var \Magento\Framework\App\RequestInterface $request */
        $request = $event->getRequest();

        //$product = $event->getProduct();
        //$response = $event->getResponse();

        if ($this->config->showCheckoutButton() && $request->getParam('cs_redirect_to_checkout')==="1") {
            $this->registry->register('cs_redirect_to_checkout', 1);
        }

        return;
    }
}