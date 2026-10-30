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

class BnplCaptureCommand implements CommandInterface
{
    private ConfigService $configService;
    private BnplApiClient $bnplApiClient;
    private LoggerInterface $logger;
    private BnplAuthorizeCommand $bnplAuthorizeCommand;

    public function __construct(
        ConfigService        $configService,
        BnplApiClient        $bnplApiClient,
        LoggerInterface      $logger,
        BnplAuthorizeCommand $bnplAuthorizeCommand
    )
    {
        $this->configService = $configService;
        $this->bnplApiClient = $bnplApiClient;
        $this->logger = $logger;
        $this->bnplAuthorizeCommand = $bnplAuthorizeCommand;
    }

    public function execute(array $commandSubject): void
    {
        $paymentDataObject = SubjectReader::readPayment($commandSubject);
        $payment = $paymentDataObject->getPayment();
        $order = $payment->getOrder();
        $amount = SubjectReader::readAmount($commandSubject);
        if (empty($amount) || !is_float($amount)) {
            $amount = $order->getGrandTotal();
        }

        if (!$payment instanceof Payment) {
            throw new LocalizedException(__('Payment instance is not valid'));
        }

        $cashpressoOrderId = $payment->getAdditionalInformation('cashpresso_bnpl_order_id');
        if (!$cashpressoOrderId) {
            $this->bnplAuthorizeCommand->execute($commandSubject, true);
            $cashpressoOrderId = $payment->getAdditionalInformation('cashpresso_bnpl_order_id');
        }
        $lastException = null;
        for ($attempt = 1; $attempt <= 3; ++$attempt) {
            try {

                $captureResult = $this->captureOrderWithBnpl($order, $payment, $cashpressoOrderId, $amount);
                $lastException = null;
                $this->storeCaptureData($payment, $captureResult);

                $this->logger->info('BNPL capture completed', [
                    'order_id' => $order->getIncrementId(),
                    'cashpresso_order_id' => $cashpressoOrderId,
                    'amount' => $amount,
                    'operation_id' => $captureResult['operationId'] ?? null,
                ]);
                break;

            } catch (\Exception $e) {
                $this->logger->error('BNPL capture failed', [
                    'order_id' => $order->getIncrementId(),
                    'error' => $e->getMessage(),
                ]);
                $lastException = new LocalizedException(__('Capture failed: %1', $e->getMessage()));
            }
            $this->logger->warning('capture attempt #' . $attempt . ' failed');
            if($attempt < 3) {
                sleep(2 ** $attempt);
            }
        }
        if ($lastException !== null) {
            throw $lastException;
        }
    }

    private function captureOrderWithBnpl(Order $order, Payment $payment, string $cashpressoOrderId, float $amount): array
    {
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

        $captureData = [
            'merchantOperationId' => $merchantOperationId,
            'basket' => $this->prepareCaptureBasket($order, $amount),
        ];

        return $this->bnplApiClient->captureOrder(
            $cashpressoOrderId,
            $captureData
        );
    }

    private function prepareCaptureBasket(Order $order, float $amount): array
    {
        return [
            'grossAmount' => round($amount * 100),
            'taxAmount' => 0,
            'currency' => $order->getOrderCurrencyCode(),
        ];
    }

    private function generateMerchantOperationId(Order $order): string
    {
        return 'capture.' . $order->getIncrementId() . '.' . time();
    }

    private function storeCaptureData(Payment $payment, array $captureResult): void
    {
        if (isset($captureResult['operationId'])) {
            $existingOperations = $payment->getAdditionalInformation('cashpresso_bnpl_capture_operations') ?: [];
            $existingOperations[] = [
                'operation_id' => $captureResult['operationId'],
                'captured_at' => date('Y-m-d H:i:s'),
                'amount' => $captureResult['amount'] ?? null,
            ];

            $payment->setTransactionId($captureResult['operationId']);

            $payment->setAdditionalInformation('cashpresso_bnpl_capture_operations', $existingOperations);
        }

        foreach ($captureResult as $key => $value) {
            if (!in_array($key, ['operationId', 'amount'])) {
                $payment->setAdditionalInformation('cashpresso_bnpl_capture_' . $key, $value);
            }
        }
    }
}
