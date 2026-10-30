<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Gateway\Command;

use Credi2\CashpressoPayments\Service\ApiClient;
use Exception;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Psr\Log\LoggerInterface;
use Throwable;

class AuthorizeCommand implements CommandInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private ApiClient       $apiClient,
    ) {
    }

    public function execute(array $commandSubject): void
    {
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $commandSubject['payment'];
        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        $this->logger->info('Cashpresso Classic authorization started', [
            'order_id' => $payment->getOrder()->getIncrementId(),
        ]);

        $cashpressoToken = $payment->getAdditionalInformation('cashpresso_token');
        if (empty($cashpressoToken)) {
            $this->logger->info('no plan selected');
            throw new Exception('no plan selected');
        }

        $order = $payment->getOrder();
        try {
            $response = $this->apiClient->buyAction($order, $cashpressoToken);
        } catch (Throwable $e) {
            $this->logger->info('API error', ['msg' => $e->getMessage()]);
            throw new Exception('API error');
        }

        if (empty($response['purchaseId'])) {
            $this->logger->info('payment not allowed', ['response' => $response]);
            throw new Exception('payment not allowed');
        }

        $payment->setTransactionId($response['purchaseId']);
        $payment->setAdditionalInformation('cashpresso_purchase_id', $response['purchaseId']);

        $payment->setIsTransactionClosed(false);
    }
}
