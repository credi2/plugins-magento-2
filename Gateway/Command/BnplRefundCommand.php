<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Gateway\Command;

use Credi2\CashpressoPayments\Service\BnplApiClient;
use Credi2\CashpressoPayments\Service\ConfigService;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Psr\Log\LoggerInterface;

class BnplRefundCommand implements CommandInterface
{
    private ConfigService $configService;
    private BnplApiClient $bnplApiClient;
    private LoggerInterface $logger;

    public function __construct(
        ConfigService   $configService,
        BnplApiClient   $bnplApiClient,
        LoggerInterface $logger
    )
    {
        $this->configService = $configService;
        $this->bnplApiClient = $bnplApiClient;
        $this->logger = $logger;
    }

    public function execute(array $commandSubject): void
    {
        $paymentDataObject = SubjectReader::readPayment($commandSubject);
        $payment = $paymentDataObject->getPayment();
        $order = $payment->getOrder();
        $amount = SubjectReader::readAmount($commandSubject);

        if (!$payment instanceof Payment) {
            throw new LocalizedException(__('Payment instance is not valid'));
        }

        try {
            $refundResult = $this->refundOrderWithBnpl($order, $payment, (float)$amount);
            $this->storeRefundData($payment, $refundResult);

            $cashpressoOrderId = $payment->getAdditionalInformation('cashpresso_bnpl_order_id');
            $this->logger->info('BNPL refund completed', [
                'order_id' => $order->getIncrementId(),
                'cashpresso_order_id' => $cashpressoOrderId,
                'amount' => $amount,
                'operation_id' => $refundResult['operationId'] ?? null,
            ]);

            $payment->setIsTransactionClosed(true);

        } catch (\Exception $e) {
            $this->logger->error('BNPL refund failed', [
                'order_id' => $order->getIncrementId(),
                'error' => $e->getMessage(),
            ]);
            throw new LocalizedException(__('Refund failed: %1', $e->getMessage()));
        }
    }

    private function refundOrderWithBnpl(Order $order, Payment $payment, float $amount): array
    {
        $cashpressoOrderId = $payment->getAdditionalInformation('cashpresso_bnpl_order_id');
        if (!$cashpressoOrderId) {
            throw new LocalizedException(__('Cashpresso order ID not found in payment data'));
        }
        $captureOperationId = null;
        $invoice = $payment->getCreditmemo()?->getInvoice();
        if ($invoice !== null) {
            $captureOperationId = $invoice->getTransactionId();
        }

        $cashpressoCaptureId = $this->getBestCaptureId($payment, $captureOperationId);
        if (!$cashpressoCaptureId) {
            throw new LocalizedException(__('No capture operation found for this order'));
        }

        $paymentMethod = $payment->getMethod();
        if ($paymentMethod === 'cashpresso_bnpl_installment') {
            $accessToken = $this->configService->getBnplInstallmentAccessToken();
            $isLive = $this->configService->isBnplInstallmentLive();
        } else {
            $accessToken = $this->configService->getBnplInvoiceAccessToken();
            $isLive = $this->configService->isBnplInvoiceLive();
        }
        $this->bnplApiClient->setApiKey($accessToken);
        $this->bnplApiClient->setIsLive($isLive);

        $merchantOperationId = $this->generateMerchantOperationId($order);

        $refundData = [
            'merchantOperationId' => $merchantOperationId,
            'captureOperationId' => $cashpressoCaptureId,
            'basket' => $this->prepareRefundBasket($order, $amount),
        ];

        return $this->bnplApiClient->createRefund(
            $cashpressoOrderId,
            $refundData
        );
    }

    private function prepareRefundBasket(Order $order, float $amount): array
    {
        return [
            'grossAmount' => round($amount * 100),
            'taxAmount' => 0,
            'currency' => $order->getOrderCurrencyCode(),
        ];
    }

    private function generateMerchantOperationId(Order $order): string
    {
        return 'refund.' . $order->getIncrementId() . '.' . time();
    }

    private function getBestCaptureId(Payment $payment, ?string $captureOperationId = null): ?string
    {
        $captureOperations = $payment->getAdditionalInformation('cashpresso_bnpl_capture_operations');

        if (!$captureOperations || !is_array($captureOperations)) {
            return null;
        }

        if ($captureOperationId !== null) {
            foreach ($captureOperations as $captureOperation) {
                if ($captureOperation['operation_id'] === $captureOperationId) {
                    return $captureOperationId;
                }
            }
        }
        // Get the latest capture operation (last one in the array)
        $latestCapture = end($captureOperations);

        return $latestCapture['operation_id'] ?? null;
    }

    private function storeRefundData(Payment $payment, array $refundResult): void
    {
        if (isset($refundResult['operationId'])) {
            $existingOperations = $payment->getAdditionalInformation('cashpresso_bnpl_refund_operations') ?: [];
            $existingOperations[] = [
                'operation_id' => $refundResult['operationId'],
                'refunded_at' => date('Y-m-d H:i:s'),
                'amount' => $refundResult['amount'] ?? null,
            ];

            $payment->setAdditionalInformation('cashpresso_bnpl_refund_operations', $existingOperations);
            $payment->setTransactionId($refundResult['operationId']);
        }

        foreach ($refundResult as $key => $value) {
            if (!in_array($key, ['operationId', 'amount'])) {
                $payment->setAdditionalInformation('cashpresso_bnpl_refund_' . $key, $value);
            }
        }
    }
}
