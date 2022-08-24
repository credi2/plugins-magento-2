<?php

namespace LimeSoda\Cashpresso\Controller\Api;

class Callback extends \Magento\Framework\App\Action\Action
{
    protected Logger $logger;

    protected Context $context;

    protected OrderStatus $orderStatus;

    protected TypeListInterface $cacheTypeListInterface;

    protected ForwardFactory $resultForwardFactory;

    protected Config $config;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \LimeSoda\Cashpresso\Gateway\Config $config,
        \LimeSoda\Cashpresso\Logger\Logger $logger,
        \LimeSoda\Cashpresso\Model\OrderStatus $orderStatus,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeListInterface,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory
    )
    {
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
