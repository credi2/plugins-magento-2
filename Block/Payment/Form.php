<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Block\Payment;

use Credi2\CashpressoPayments\Service\ConfigService;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Form as PaymentForm;
use Magento\Payment\Model\Config;

class Form extends PaymentForm
{
    public function __construct(
        Context $context,
        Config $paymentConfig,
        private readonly CheckoutSession $checkoutSession,
        private readonly PricingHelper $pricingHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }



    public function getQuoteTotal(): float
    {
        if ($quote = $this->checkoutSession->getQuote()) {
            return (float)$quote->getGrandTotal();
        }
        return 0.0;
    }

    public function getQuoteCurrency(): string
    {
        if ($quote = $this->checkoutSession->getQuote()) {
            return $quote->getQuoteCurrencyCode() ?? 'EUR';
        }
        return 'EUR';
    }

    public function getFormattedAmount(): string
    {
        return $this->pricingHelper->currency($this->getQuoteTotal(), true, false);
    }
}
