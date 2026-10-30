<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Block\Product;

use Credi2\CashpressoPayments\Model\Config\Source\LabelType;
use Credi2\CashpressoPayments\Service\ApiClient;
use Credi2\CashpressoPayments\Service\ConfigService;
use Magento\Catalog\Model\Product;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Widget extends Template
{
    protected $_template = 'Credi2_CashpressoPayments::product/cashpresso-widget.phtml';

    public $pageType = 'detail';

    public function __construct(
        Context $context,
        private readonly Registry $registry,
        private readonly ConfigService $configService,
        private readonly Resolver $localeResolver,
        private readonly ApiClient $apiClient,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function isEnabled(): bool
    {
        return $this->isActiveOnCurrentPage() &&
               $this->configService->isActive() &&
               $this->getProduct() !== null &&
               $this->getApiKey() !== null;
    }

    public function isActiveOnCurrentPage()
    {
        if ($this->pageType === 'detail') {
            return $this->configService->isShowOnProductDetailPage();
        } elseif ($this->pageType === 'list') {
            return $this->configService->isShowOnProductListingPage();
        }
        return false;
    }

    public function setPageType(string $pageType)
    {
        if (in_array($pageType, ['detail', 'list'])) {
            $this->pageType = $pageType;
        }
    }

    public function getProduct(): ?Product
    {
        $p = $this->getData('product');
        if ($p instanceof Product) {
            return $p;
        }

        $p = $this->registry->registry('current_product');
        return ($p instanceof Product) ? $p : null;
    }

    public function getProductPrice(): float
    {
        if ($product = $this->getProduct()) {
            return (float)$product->getFinalPrice();
        }
        return 0.0;
    }

    public function getApiKey(): ?string
    {
        return $this->configService->getApiKey();
    }

    public function isLive(): bool
    {
        return $this->configService->isLive();
    }

    public function getInterestFreeDays(): int
    {
        return $this->configService->getInterestFreeDays();
    }

    public function getLocale(): string
    {
        return $this->localeResolver->getLocale();
    }

    public function getLanguageCode(): string
    {
        return substr($this->getLocale(), 0, 2);
    }

    public function getLabelType(): string
    {
        return $this->configService->getLabelType();
    }

    public function isDynamicLabel(): bool
    {
        return $this->getLabelType() === LabelType::LABEL_TYPE_DYNAMIC;
    }

    public function isStaticLabel(): bool
    {
        return $this->getLabelType() === LabelType::LABEL_TYPE_STATIC;
    }

    public function getStaticLabelMonthlyAmount(): float
    {
        $price = $this->getProductPrice();

        // Get values from partner info (these should ideally be cached from getPartnerInfo API call)
        $paybackRate = $this->getPaybackRate();
        $minPaybackAmount = $this->getMinPaybackAmount();
        $financingLimit = $this->getFinancingLimit();

        // Apply financing limit if set
        if ($financingLimit && $price > $financingLimit) {
            $price = $financingLimit;
        }

        // Calculate monthly amount: min(P, max(minPaybackAmount, P * 0.01 * paybackRate))
        return min($price, max($minPaybackAmount, $price * 0.01 * $paybackRate));
    }

    public function shouldShowStaticLabel(): bool
    {
        if (!$this->isStaticLabel()) {
            return false;
        }

        $price = $this->getProductPrice();
        $minPaybackAmount = $this->getMinPaybackAmount();
        $financingLimit = $this->getFinancingLimit();

        // Hide if price is smaller than minimum payback amount
        if ($price < $minPaybackAmount) {
            return false;
        }

        // Hide if price is higher than total limit
        if ($financingLimit && $price > $financingLimit) {
            return false;
        }

        return true;
    }

    private function getPartnerInfo(): ?array
    {
        try {
            return $this->apiClient->getCachedPartnerInfo();
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getPaybackRate(): float
    {
        $partnerInfo = $this->getPartnerInfo();
        return $partnerInfo['paybackRate'] ?? 3.00;
    }

    private function getMinPaybackAmount(): float
    {
        $partnerInfo = $this->getPartnerInfo();
        return $partnerInfo['minPaybackAmount'] ?? 2.0;
    }

    private function getFinancingLimit(): ?float
    {
        $partnerInfo = $this->getPartnerInfo();
        return $partnerInfo['limit']['financing'] ?? null;
    }
}
