<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Psr\Log\LoggerInterface;

class CaptureCommand implements CommandInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(array $commandSubject): void
    {
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $commandSubject['payment'];
        $payment = $paymentDO->getPayment();

        $this->logger->info('Cashpresso Classic capture started', [
            'order_id' => $payment->getOrder()->getIncrementId()
        ]);

        $payment->setIsTransactionClosed(true);
    }
}
