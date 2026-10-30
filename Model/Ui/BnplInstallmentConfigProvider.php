<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Model\Ui;

use Credi2\CashpressoPayments\Service\ConfigService;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Psr\Log\LoggerInterface;

class BnplInstallmentConfigProvider implements ConfigProviderInterface
{
    public function __construct(
        private readonly ConfigService   $configService,
        private readonly CheckoutSession $checkoutSession,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getConfig(): array
    {
        $config = [
            'payment' => [
                'cashpresso_bnpl_installment' => [
                    'isLive' => $this->configService->isBnplInstallmentLive(),
                    'locale' => $this->configService->getLanguageCode(),
                    'scriptUrl' => $this->getBnplScriptUrl(),
                    'mode' => $this->configService->isBnplInstallmentLive() ? 'prod' : 'test',
                ],
            ],
        ];

        // Add authorization data if available from checkout session
        try {
            $quote = $this->checkoutSession->getQuote();
            if ($quote && $quote->getId()) {
                $payment = $quote->getPayment();
                if ($payment && $payment->getMethod() === 'cashpresso_bnpl_installment') {
                    $authorizationId = $payment->getAdditionalInformation('cashpresso_bnpl_authorization_id');
                    $clientToken = $payment->getAdditionalInformation('client_token');

                    if ($authorizationId && $clientToken) {
                        $config['payment']['cashpresso_bnpl_installment']['authorizationId'] = $authorizationId;
                        $config['payment']['cashpresso_bnpl_installment']['clientToken'] = $clientToken;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to get BNPL authorization data from session', [
                'error' => $e->getMessage(),
            ]);
        }

        return $config;
    }

    /**
     * Get BNPL script URL based on environment
     */
    private function getBnplScriptUrl(): string
    {
        if ($this->configService->isBnplInstallmentLive()) {
            return 'https://www.purchase.bnpl.cashpresso.com/js/bnpl-wizard.min.js';
        } else {
            return 'https://www.bnpl-checkout-dialog.test-cashpresso.com/js/bnpl-wizard.min.js';
        }
    }
}
