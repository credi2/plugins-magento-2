<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Controller\Bnpl;

use Credi2\CashpressoPayments\Service\BnplApiClient;
use Credi2\CashpressoPayments\Service\BnplBasketBuilder;
use Credi2\CashpressoPayments\Service\ConfigService;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Initialize implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory      $jsonFactory,
        private readonly CheckoutSession  $checkoutSession,
        private readonly CustomerSession  $customerSession,
        private readonly BnplApiClient     $bnplApiClient,
        private readonly BnplBasketBuilder $basketBuilder,
        private readonly ConfigService     $configService,
        private readonly LoggerInterface   $logger
    ) {
    }

    public function execute(): ResultInterface
    {
        $result = $this->jsonFactory->create();

        try {
            // Get payment method from request
            $paymentMethod = $this->request->getParam('payment_method');
            if (empty($paymentMethod)) {
                throw new LocalizedException(__('Payment method not specified'));
            }

            $quote = $this->checkoutSession->getQuote();
            if (!$quote->getId()) {
                throw new LocalizedException(__('No active quote found'));
            }

            // Initialize BNPL checkout and authorization
            $initData = $this->initializeBnplCheckout($quote, $paymentMethod);

            // Store authorization data in quote payment for frontend use
            $payment = $quote->getPayment();
            $payment->setMethod($paymentMethod);
            $payment->setAdditionalInformation('cashpresso_bnpl_authorization_id', $initData['authorizationId']);
            $payment->setAdditionalInformation('client_token', $initData['clientToken']);
            $quote->save();

            $result->setData([
                'success' => true,
                'authorizationId' => $initData['authorizationId'],
                'clientToken' => $initData['clientToken'],
            ]);

        } catch (\Exception $e) {
            $this->logger->error('BNPL Initialize error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            $result->setData([
                'success' => false,
                'message' => __('Failed to initialize BNPL payment: %1', $e->getMessage()),
            ]);
        }

        return $result;
    }

    /**
     * Initialize BNPL checkout and authorization
     */
    private function initializeBnplCheckout($quote, string $paymentMethod): array
    {
        $billingAddress = $quote->getBillingAddress();

        // Configure API client based on payment method
        if ($paymentMethod === 'cashpresso_bnpl_installment') {
            $accessToken = $this->configService->getBnplInstallmentAccessToken();
            $merchantId = $this->configService->getBnplInstallmentMerchantId();
            $isLive = $this->configService->isBnplInstallmentLive();
            $productType = 'INSTALLMENT';
        } else {
            $accessToken = $this->configService->getBnplInvoiceAccessToken();
            $merchantId = $this->configService->getBnplInvoiceMerchantId();
            $isLive = $this->configService->isBnplInvoiceLive();
            $productType = 'INVOICE';
        }

        $this->bnplApiClient->setApiKey($accessToken);
        $this->bnplApiClient->setIsLive($isLive);

        // Step 1: Checkout Init
        $sessionId = $this->customerSession->getSessionId() ?: session_id();

        $checkoutData = [
            'merchantId' => $merchantId,
            'sessionId' => $sessionId,
            'channel' => 'ECOM',
            'customerCountry' => $billingAddress->getCountryId(),
            'basket' => $this->basketBuilder->buildFromQuote($quote),
        ];

        $checkoutResponse = $this->bnplApiClient->checkoutInit($checkoutData);

        if (empty($checkoutResponse['checkout']['checkoutId'])) {
            throw new LocalizedException(__('Failed to initialize BNPL checkout'));
        }

        // Step 2: Authorization Init
        $checkoutId = $checkoutResponse['checkout']['checkoutId'];

        $authRequest = [
            'sessionId' => $sessionId,
            'channel' => 'ECOM',
            'productType' => $productType,
            'basket' => $this->basketBuilder->buildFromQuote($quote),
            'billingCustomer' => $this->prepareBillingCustomerData($quote),
            'shippingAddress' => $this->prepareShippingAddressData($quote),
        ];

        $authResponse = $this->bnplApiClient->authorizationInit($checkoutId, $authRequest);

        if (empty($authResponse['authorizationId'])) {
            throw new LocalizedException(__('Failed to start BNPL authorization'));
        }

        return [
            'checkoutId' => $checkoutId,
            'authorizationId' => $authResponse['authorizationId'],
            'clientToken' => $authResponse['clientToken'] ?? null,
        ];
    }

    /**
     * Prepare billing customer data for authorization
     */
    private function prepareBillingCustomerData($quote): array
    {
        $billingAddress = $quote->getBillingAddress();

        $billingCustomer = [
            'firstname' => $billingAddress->getFirstname(),
            'lastname' => $billingAddress->getLastname(),
            'email' => $quote->getCustomerEmail() ?: $billingAddress->getEmail(),
            'language' => $this->configService->getLanguageCode(),
            'street' => implode(' ', $billingAddress->getStreet()),
            'zip' => $billingAddress->getPostcode(),
            'city' => $billingAddress->getCity(),
            'country' => $billingAddress->getCountryId(),
        ];

        if ($billingAddress->getTelephone()) {
            $billingCustomer['phone'] = $billingAddress->getTelephone();
        }

        return $billingCustomer;
    }

    /**
     * Prepare shipping address data for authorization
     */
    private function prepareShippingAddressData($quote): array
    {
        $shippingAddress = $quote->getShippingAddress();
        $billingAddress = $quote->getBillingAddress();

        $address = $shippingAddress ?: $billingAddress;

        $shippingData = [
            'firstname' => $address->getFirstname(),
            'lastname' => $address->getLastname(),
            'street' => implode(' ', $address->getStreet()),
            'zip' => $address->getPostcode(),
            'city' => $address->getCity(),
            'country' => $address->getCountryId(),
        ];

        if ($address->getCompany()) {
            $shippingData['company'] = $address->getCompany();
        }

        return $shippingData;
    }
}
