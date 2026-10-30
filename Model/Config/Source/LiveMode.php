<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LiveMode implements OptionSourceInterface
{
    public const LIVE_YES = 1;
    public const LIVE_NO = 0;

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::LIVE_NO,
                'label' => __('Test')
            ],
            [
                'value' => self::LIVE_YES,
                'label' => __('Live')
            ]
        ];
    }
}