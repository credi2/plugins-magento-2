<?php

namespace LimeSoda\Cashpresso\Controller\Api;

use LimeSoda\Cashpresso\Logger\Logger;
use Magento\Backend\App\Action\Context;
use LimeSoda\Cashpresso\Gateway\Config;
use LimeSoda\Cashpresso\Model\OrderStatus;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Controller\Result\ForwardFactory;

class Callback extends \Magento\Framework\App\Action\Action
{
    protected Logger $logger;

    protected Context $context;

    protected OrderStatus $orderStatus;

    protected TypeListInterface $cacheTypeListInterface;

    protected ForwardFactory $resultForwardFactory;

    protected Config $config;

    public function __construct(
        Context $context,
        Config $config,
        Logger $logger,
        OrderStatus $orderStatus,
        TypeListInterface $cacheTypeListInterface,
        ForwardFactory $resultForwardFactory
    ) {
        $this->logger = $logger;

        $this->context = $context;

        $this->orderStatus = $orderStatus;

        $this->resultForwardFactory = $resultForwardFactory;

        parent::__construct($context);

        $this->cacheTypeListInterface = $cacheTypeListInterface;

        $this->config = $config;
    }

    public function execute()
    {
        /* Invalidate Full Page Cache */
        $this->cacheTypeListInterface->invalidate('full_page');

        if ($this->config->isDebugEnabled()){
            $this->logger->debug('Callback request:');
            $this->logger->debug(print_r($this->getRequest()->getParams(), true));
        }

        try {
            $order = $this->orderStatus->getOrder();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->error($e->getMessage());
            //$this->_forward('noroute');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            //$this->_forward('noroute');
        }

        return;
    }
}
