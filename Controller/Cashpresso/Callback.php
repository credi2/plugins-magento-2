<?php

namespace Credi2\CashpressoPayments\Controller\Cashpresso;

use Credi2\CashpressoPayments\Service\ConfigService;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class Callback implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private JsonFactory $jsonFactory;
    private RequestInterface $request;
    private LoggerInterface $logger;
    private ConfigService $configService;
    private OrderRepositoryInterface $orderRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    public function __construct(
        JsonFactory              $jsonFactory,
        RequestInterface         $request,
        LoggerInterface          $logger,
        ConfigService            $configService,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder    $searchCriteriaBuilder
    )
    {
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->logger = $logger;
        $this->configService = $configService;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function execute(): Json
    {
        $body = $this->request->getContent();
        $this->logger->info('callback called', ['body' => $body]);

        if (empty($body)) {
            $this->logger->warning('callback empty body');
            return $this->jsonFactory->create()->setData(['success' => false, 'reason' => 'empty body']);
        }

        $data = json_decode($body, true);
        if (empty($data)) {
            $this->logger->error('callback body not json', ['body' => $body]);
            return $this->jsonFactory->create()->setData(['success' => false, 'reason' => 'invalid json']);
        }

        $transactionNumber = $data['referenceId'];
        $orderNumber = $data['usage'];
        $status = $data['status'];
        $hash = $data['verificationHash'];

        $hashBase = $this->configService->getApiSecret() . ';' .
            $status . ';' .
            $transactionNumber . ';' .
            $orderNumber;

        if (hash('sha512', $hashBase) !== $hash) {
            return $this->jsonFactory->create()->setData(['success' => false]);
        }

        $this->setOrderStatus($orderNumber, $status);

        return $this->jsonFactory->create()->setData(['success' => true]);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    private function setOrderStatus(string $orderNumber, string $status): void
    {
        try {
            $order = $this->getOrderByIncrementId($orderNumber);
            if (!$order) {
                $this->logger->warning('Order not found', ['orderNumber' => $orderNumber]);
                return;
            }

            $payment = $order->getPayment();

            if ($status === 'SUCCESS') {
                // Mark payment as paid and create invoice
                $payment->setTransactionId($orderNumber)
                    ->setIsTransactionClosed(false)
                    ->registerCaptureNotification($order->getGrandTotal());
            } elseif ($status === 'TIMEOUT') {
                // Mark order as failed
                $order->setState(Order::STATE_CANCELED)
                    ->setStatus(Order::STATE_CANCELED);

            } else {
                // Cancel order for any other status
                $order->setState(Order::STATE_CANCELED)
                    ->setStatus(Order::STATE_CANCELED);
            }

            $this->orderRepository->save($order);
            $this->logger->info('Order status updated', ['orderNumber' => $orderNumber, 'status' => $status]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update order status', [
                'orderNumber' => $orderNumber,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getOrderByIncrementId(string $incrementId): ?Order
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $incrementId)
            ->create();

        $orders = $this->orderRepository->getList($searchCriteria);

        if ($orders->getTotalCount() > 0) {
            $items = $orders->getItems();
            return reset($items) ?: null;
        }

        return null;
    }
}
