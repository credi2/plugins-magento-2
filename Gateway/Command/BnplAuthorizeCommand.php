<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Gateway\Command;

use Credi2\CashpressoPayments\Service\BnplApiClient;
use Credi2\CashpressoPayments\Service\BnplBasketBuilder;
use Credi2\CashpressoPayments\Service\ConfigService;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Psr\Log\LoggerInterface;

class BnplAuthorizeCommand implements CommandInterface
{
    public function __construct(
        private readonly BnplApiClient     $bnplApiClient,
        private readonly BnplBasketBuilder $basketBuilder,
        private readonly ConfigService     $configService,
        private readonly LoggerInterface   $logger,
    ) {
    }

    public function execute($commandSubject, $fromCapture = false): void
    {
        $paymentDataObject = SubjectReader::readPayment($commandSubject);
        /** @var Payment $payment */
        $payment = $paymentDataObject->getPayment();
        $order = $payment->getOrder();

        try {
            // Get authorization ID from payment additional information
            $authorizationId = $payment->getAdditionalInformation('cashpresso_bnpl_authorization_id');

            if (empty($authorizationId)) {
                throw new LocalizedException(__('Authorization ID not found. Please retry the payment.'));
            }

            $this->logger->info('BnplAuthorizeCommand - starting order placement', [
                'order_id' => $order->getIncrementId(),
                'authorization_id' => $authorizationId,
            ]);

            $placeOrderResult = $this->placeOrder($order, $payment, $authorizationId);
            $this->storeBnplOrderData($payment, $placeOrderResult, $fromCapture);
            if (!$fromCapture) {
                $this->setOrderAuthorized($order, $payment);
            }

            $this->logger->info('BnplAuthorizeCommand completed successfully', [
                'order_id' => $order->getIncrementId(),
                'bnpl_order_id' => $placeOrderResult['order']['orderId'] ?? null,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('BnplAuthorizeCommand failed', [
                'order_id' => $order->getIncrementId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new LocalizedException(__('Payment authorization failed: %1', $e->getMessage()));
        }
    }

    /**
     * Place order with BNPL payment provider
     */
    private function placeOrder(Order $order, Payment $payment, string $authorizationId): array
    {
        $paymentMethod = $payment->getMethod();

        // Configure API client with appropriate access token
        if ($paymentMethod === 'cashpresso_bnpl_installment') {
            $accessToken = $this->configService->getBnplInstallmentAccessToken();
            $isLive = $this->configService->isBnplInstallmentLive();
            $productType = 'INSTALLMENT';
        } else {
            $accessToken = $this->configService->getBnplInvoiceAccessToken();
            $isLive = $this->configService->isBnplInvoiceLive();
            $productType = 'INVOICE';
        }

        $this->bnplApiClient->setApiKey($accessToken);
        $this->bnplApiClient->setIsLive($isLive);

        // Prepare order data for BNPL API
        $orderData = [
            'merchantOperationId' => $this->generateMerchantOperationId($order),
            'basket' => $this->basketBuilder->buildFromOrder($order),
            'billingCustomer' => $this->prepareBillingCustomerData($order),
            'shippingAddress' => $this->prepareShippingAddressData($order),
            'merchantReferences' => [
                [
                    'name' => 'orderNumber',
                    'value' => $order->getIncrementId(),
                ],

            ],
        ];

        $this->logger->info('BNPL Place Order request', [
            'order_id' => $order->getIncrementId(),
            'authorization_id' => $authorizationId,
            'product_type' => $productType,
        ]);

        // Call BNPL API to place order
        $response = $this->bnplApiClient->placeOrder($authorizationId, $orderData);

        if (empty($response['order']['orderId'])) {
            throw new LocalizedException(__('Failed to place order - no order ID returned'));
        }

        return $response;
    }

    /**
     * Store BNPL order data in payment additional information
     */
    private function storeBnplOrderData(Payment $payment, array $placeOrderResult, $fromCapture = false): void
    {
        $orderId = $placeOrderResult['order']['orderId'];
        $orderData = $placeOrderResult['order'];

        // Store BNPL order information
        $payment->setAdditionalInformation('cashpresso_bnpl_order_id', $orderId);
        $payment->setAdditionalInformation('cashpresso_bnpl_order_data', $orderData);

        if (!$fromCapture) {
            $payment->setTransactionId($orderId);
        }
    }

    /**
     * Set order state to authorized
     */
    private function setOrderAuthorized(Order $order, Payment $payment): void
    {
        // Mark payment as authorized
        $payment->setIsTransactionClosed(false);
    }

    /**
     * Generate unique merchant operation ID
     */
    private function generateMerchantOperationId(Order $order): string
    {
        return sprintf(
            '%s.%s.%s',
            $order->getIncrementId(),
            session_id(),
            uniqid()
        );
    }

    /**
     * Prepare billing customer data for BNPL API
     */
    private function prepareBillingCustomerData(Order $order): array
    {
        $billingAddress = $order->getBillingAddress();

        $billingCustomer = [
            'firstname' => $billingAddress->getFirstname(),
            'lastname' => $billingAddress->getLastname(),
            'email' => $order->getCustomerEmail(),
            'language' => $this->configService->getLanguageCode(),
            'street' => implode(' ', $billingAddress->getStreet()),
            'zip' => $billingAddress->getPostcode(),
            'city' => $billingAddress->getCity(),
            'country' => $billingAddress->getCountryId(),
        ];

        // Add phone if available
        if ($billingAddress->getTelephone()) {
            $billingCustomer['phone'] = $billingAddress->getTelephone();
        }

        return $billingCustomer;
    }

    /**
     * Prepare shipping address data for BNPL API
     */
    private function prepareShippingAddressData(Order $order): array
    {
        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();

        // Use shipping address if available, otherwise fall back to billing address
        $address = $shippingAddress ?: $billingAddress;

        $shippingData = [
            'firstname' => $address->getFirstname(),
            'lastname' => $address->getLastname(),
            'street' => implode(' ', $address->getStreet()),
            'zip' => $address->getPostcode(),
            'city' => $address->getCity(),
            'country' => $address->getCountryId(),
        ];

        // Add company if available
        if ($address->getCompany()) {
            $shippingData['company'] = $address->getCompany();
        }

        return $shippingData;
    }
}
