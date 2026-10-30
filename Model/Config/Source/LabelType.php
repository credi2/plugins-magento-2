<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LabelType implements OptionSourceInterface
{
    public const LABEL_TYPE_DYNAMIC = 'dynamic';
    public const LABEL_TYPE_STATIC = 'static';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::LABEL_TYPE_DYNAMIC,
                'label' => __('Dynamic Label')
            ],
            [
                'value' => self::LABEL_TYPE_STATIC,
                'label' => __('Static Label')
            ]
        ];
    }
}