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

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    public function __construct(
        \LimeSoda\Cashpresso\Gateway\Config $config,
        \LimeSoda\Cashpresso\Api\Checkout $checkout,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory,
        \Magento\Framework\App\ResponseFactory $responseFactory
    )
    {
        $this->config = $config;

        $this->checkout = $checkout;

        $this->resultRedirectFactory = $redirectFactory;

        $this->url = $url;

        $this->responseFactory = $responseFactory;
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

        if ($this->config->showCheckoutButton() && $request->getParam('cs_redirect_to_checkout')) {
            $redirectionUrl = $this->url->getUrl($this->config->getCheckoutUrl());
            $this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();

            exit;
        }

        return;
    }
}