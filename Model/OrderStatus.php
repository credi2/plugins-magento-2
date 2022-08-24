<?php

namespace LimeSoda\Cashpresso\Model;

class OrderStatus
{
    protected $config;

    protected $logger;

    protected $order;

    protected $transaction;

    protected $orderRepository;

    public function __construct(
        \LimeSoda\Cashpresso\Gateway\Config $config,
        \LimeSoda\Cashpresso\Logger\Logger $logger,
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    )
    {
        /*\LimeSoda\Cashpresso\Helper\Store $store,
        \LimeSoda\Cashpresso\Gateway\Config $config,
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\Webapi\Rest\Request $request,
        \Magento\Framework\Webapi\Rest\Request\Deserializer\Json $json,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,*/
        $this->config = $config;

        $this->logger = $logger;

        $this->order = $order;

        $this->transaction = $transaction;

        /** @var \Magento\Sales\Model\OrderRepository orderRepository */
        $this->orderRepository = $orderRepository;
    }

    public function getOrder()
    {
        if ($this->config->isDebugEnabled()) {
            $this->logger->debug('request trigger');
        }

        $order = null;

        if ($this->config->isDebugEnabled()) {
            $this->logger->debug(print_r($this->getRowBody(), true));
        }

        if ($response = @json_decode($this->getRowBody(), true)) {
            if (!$this->hashCheck($response, $this->config->getSecretKey())){
                throw new \Magento\Framework\Exception\LocalizedException(__('Verification hash is wrong.'));
            }

            if (empty($response['usage'])) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Order ID is empty.'));
            }

            $order = $this->order->loadByIncrementId($response['usage']);

            if (!$order->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Order %d was not found', $order->getId()));
            }

            $this->setOrderStatus($order, $response);
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('Response is empty.'));
        }

        return $order;
    }

    protected function setOrderStatus( \Magento\Sales\Model\Order $order, $response)
    {
        if (empty($response['status'])){
            throw new \Magento\Framework\Exception\LocalizedException(__('Response is empty.'));
        }

        switch ($response['status']) {
            case 'SUCCESS':
                $orderState = $order->getState();

                if ($orderState === \Magento\Sales\Model\Order::STATE_NEW) {
                    if ($order->canInvoice()) {
                        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
                        $invoice = $order->prepareInvoice();
                        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                        $invoice->register();

                        $this->transaction->addObject(
                            $invoice
                        )->addObject(
                            $invoice->getOrder()
                        );

                        $this->transaction->save();
                    }

                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true);
                    $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING, true);

                    $this->orderRepository->save($order);
                }
                break;
            case 'CANCELLED':
            case 'TIMEOUT':
                $orderState = $order->getState();

                if ($orderState === \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT || $orderState === \Magento\Sales\Model\Order::STATE_NEW) {

                    $order->cancel();

                    $this->orderRepository->save($order);
                }
                break;
        }

        return $this;
    }

    /**
     * @param $response
     * @param $key
     * @return bool
     */
    public function hashCheck($response, $key)
    {
        $remoteHash = $response['verificationHash']??'';
        $status = $response['status']??'';
        $referenceId = $response['referenceId']??'';
        $usage = $response['usage']??'';

        $localHash = hash('sha512', $key . ';' . $status . ';' . $referenceId . ';' . $usage);

        return $remoteHash == $localHash;
    }

    protected function getRowBody()
    {
        $rawRequestBody = file_get_contents('php://input');

        if (is_string($rawRequestBody) && strlen(trim($rawRequestBody)) > 0) {
            $rawRequestBody = $rawRequestBody;
        } else {
            $rawRequestBody = false;
        }

        return $rawRequestBody;
    }
}
