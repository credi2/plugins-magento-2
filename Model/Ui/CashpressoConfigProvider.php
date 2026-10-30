<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Model\Ui;

use Credi2\CashpressoPayments\Service\ConfigService;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Psr\Log\LoggerInterface;

class CashpressoConfigProvider implements ConfigProviderInterface
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
                'cashpresso' => [
                    'apiKey' => $this->configService->getApiKey(),
                    'interestFreeDays' => $this->configService->getInterestFreeDays(),
                    'isLive' => $this->configService->isLive(),
                    'locale' => $this->configService->getLanguageCode(),
                    'customerData' => $this->getCustomerData(),
                    'choosePlanMessage' => __('Please choose a payment plan.')->render(),
                ],
            ],
        ];

        return $config;
    }

    /**
     * Get customer data for cashpresso script
     */
    private function getCustomerData(): array
    {
        $customerData = [];

        try {
            $quote = $this->checkoutSession->getQuote();

            if ($quote && $quote->getId()) {
                $customer = $quote->getCustomer();
                $billingAddress = $quote->getBillingAddress();

                if ($customer && $customer->getId()) {
                    $customerData['email'] = $customer->getEmail();
                    $customerData['firstName'] = $customer->getFirstname();
                    $customerData['lastName'] = $customer->getLastname();

                    if ($customer->getDob()) {
                        $customerData['birthday'] = date('Y-m-d', strtotime($customer->getDob()));
                    }
                }

                if ($billingAddress) {
                    $customerData['email'] = $customerData['email'] ?? $billingAddress->getEmail();
                    $customerData['firstName'] = $customerData['firstName'] ?? $billingAddress->getFirstname();
                    $customerData['lastName'] = $customerData['lastName'] ?? $billingAddress->getLastname();
                    $customerData['country'] = $billingAddress->getCountryId();
                    $customerData['city'] = $billingAddress->getCity();
                    $customerData['zipcode'] = $billingAddress->getPostcode();
                    $customerData['street'] = implode(' ', $billingAddress->getStreet() ?? []);
                    $customerData['phoneNumber'] = $billingAddress->getTelephone();
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to get customer data for cashpresso config', [
                'error' => $e->getMessage(),
            ]);
        }

        return array_filter($customerData);
    }
}
