<?php


namespace LimeSoda\Cashpresso\Model\Adminhtml\System\Config\Source;

class Widget implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label'=> __('Static product label')],
            ['value' => 1, 'label'=> __('Dynamic product label')]
        ];
    }
}