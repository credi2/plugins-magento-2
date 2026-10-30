<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Service;

use Credi2\CashpressoPayments\Model\Config\Source\LabelType;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigService
{    private const CONFIG_PATH_PREFIX = 'payment/cashpresso/';
    private const CONFIG_PATH_BNPL_INSTALLMENT_PREFIX = 'payment/cashpresso_bnpl_installment/';
    private const CONFIG_PATH_BNPL_INVOICE_PREFIX = 'payment/cashpresso_bnpl_invoice/';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly UrlInterface         $urlBuilder
    )
    {
    }

    public function isLive(?int $storeId = null): bool
    {
        return (bool)$this->getValue('is_live', $storeId);
    }

    public function getValue(string $key, ?int $storeId = null, string $prefix = self::CONFIG_PATH_PREFIX)
    {
        return $this->scopeConfig->getValue(
            $prefix . $key,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getApiKey(?int $storeId = null): ?string
    {
        return $this->getValue('api_key', $storeId);
    }

    public function getApiSecret(?int $storeId = null): ?string
    {
        return $this->getValue('api_secret', $storeId);
    }

    public function getValidityHours(?int $storeId = null): int
    {
        return (int)$this->getValue('validity_hours', $storeId) ?: 24;
    }

    public function getInterestFreeDays(?int $storeId = null): int
    {
        return (int)$this->getValue('interest_free_days', $storeId);
    }

    public function isShowOnProductDetailPage(?int $storeId = null): bool
    {
        return (bool)$this->getValue('show_on_product_detail_page', $storeId);
    }

    public function isShowOnProductListingPage(?int $storeId = null): bool
    {
        return (bool)$this->getValue('show_on_product_listing_page', $storeId);
    }

    public function isActive(?int $storeId = null): bool
    {
        return (bool)$this->getValue('active', $storeId);
    }

    public function getLabelType(?int $storeId = null): string
    {
        return $this->getValue('label_type', $storeId) ?: LabelType::LABEL_TYPE_DYNAMIC;
    }

    public function getCallbackUrl(): string
    {
        return $this->urlBuilder->getUrl('cashpresso/cashpresso/callback');
    }

    // BNPL Installment methods
    public function isBnplInstallmentLive(?int $storeId = null): bool
    {
        return (bool)$this->getValue('is_live', $storeId, self::CONFIG_PATH_BNPL_INSTALLMENT_PREFIX);
    }

    public function getBnplInstallmentMerchantId(?int $storeId = null): ?string
    {
        return $this->getValue('merchant_id', $storeId, self::CONFIG_PATH_BNPL_INSTALLMENT_PREFIX);
    }

    public function getBnplInstallmentAccessToken(?int $storeId = null): ?string
    {
        return $this->getValue('access_token', $storeId, self::CONFIG_PATH_BNPL_INSTALLMENT_PREFIX);
    }

    public function getBnplInstallmentPaymentAction(?int $storeId = null): ?string
    {
        return $this->getValue('payment_action', $storeId, self::CONFIG_PATH_BNPL_INSTALLMENT_PREFIX);
    }

    // BNPL Invoice methods
    public function isBnplInvoiceLive(?int $storeId = null): bool
    {
        return (bool)$this->getValue('is_live', $storeId, self::CONFIG_PATH_BNPL_INVOICE_PREFIX);
    }

    public function getBnplInvoiceMerchantId(?int $storeId = null): ?string
    {
        return $this->getValue('merchant_id', $storeId, self::CONFIG_PATH_BNPL_INVOICE_PREFIX);
    }

    public function getBnplInvoiceAccessToken(?int $storeId = null): ?string
    {
        return $this->getValue('access_token', $storeId, self::CONFIG_PATH_BNPL_INVOICE_PREFIX);
    }

    public function getBnplInvoicePaymentAction(?int $storeId = null): ?string
    {
        return $this->getValue('payment_action', $storeId, self::CONFIG_PATH_BNPL_INVOICE_PREFIX);
    }

    /**
     * Get language code for API calls
     */
    public function getLanguageCode(?int $storeId = null): string
    {
        // Get store locale and convert to language code
        $locale = $this->scopeConfig->getValue(
            'general/locale/code',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        // Convert locale (en_US) to language code (en)
        return substr($locale, 0, 2);
    }
}
