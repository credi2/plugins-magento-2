<?php

namespace LimeSoda\Cashpresso\Model\Adminhtml\System\Config\Source;

class Mode implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 1, 'label'=> __('Live')],
            ['value' => 0, 'label'=> __('Test')],
            ['value' => 0, 'label'=> __('Dev')]
        ];
    }
}