<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class PaymentAction implements OptionSourceInterface
{
    public const PAYMENT_ACTION_AUTHORIZE = 'authorize';
    public const PAYMENT_ACTION_AUTHORIZE_CAPTURE = 'authorize_capture';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::PAYMENT_ACTION_AUTHORIZE,
                'label' => __('Authorize Only')
            ],
            [
                'value' => self::PAYMENT_ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorize, Capture')
            ]
        ];
    }
}
