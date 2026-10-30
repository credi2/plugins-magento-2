<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Gateway\Config;

use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Config\ValueHandlerInterface;

class RedirectUrlValueHandler implements ValueHandlerInterface
{
    public function __construct(
        private readonly UrlInterface $urlBuilder
    ) {
    }

    /**
     * @inheritDoc
     */
    public function handle(array $subject, $storeId = null)
    {
        return $this->urlBuilder->getUrl('cashpresso/bnpl/checkout');
    }
}
