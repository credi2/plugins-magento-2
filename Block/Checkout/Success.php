<?php

namespace Credi2\CashpressoPayments\Block\Checkout;

use Credi2\CashpressoPayments\Service\ConfigService;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;

class Success extends Template
{
    private Session $checkoutSession;
    private ConfigService $configService;

    public function __construct(
        Context       $context,
        Session       $checkoutSession,
        ConfigService $configService,
        array         $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->configService = $configService;
    }

    public function shouldShowWidget(): bool
    {
        $order = $this->getOrder();
        if (!$order) {
            return false;
        }

        $payment = $order->getPayment();
        if (!$payment) {
            return false;
        }

        $paymentMethod = $payment->getMethod();
        return in_array($paymentMethod, ['cashpresso']);
    }

    public function getOrder(): ?Order
    {
        return $this->checkoutSession->getLastRealOrder();
    }

    public function getApiKey(): string
    {
        return $this->configService->getApiKey();
    }

    public function getPurchaseId(): string
    {
        $order = $this->getOrder();
        if (!$order) {
            return '';
        }

        $payment = $order->getPayment();
        if (!$payment) {
            return '';
        }

        return (string)$payment->getAdditionalInformation('cashpresso_purchase_id') ?? '';
    }

    public function getMode(): string
    {
        return $this->configService->isLive() ? 'live' : 'test';
    }

    public function getLocale(): string
    {
        return $this->configService->getLanguageCode() ?: 'de';
    }
}
