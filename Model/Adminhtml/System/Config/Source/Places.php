<?php

namespace LimeSoda\Cashpresso\Model\Adminhtml\System\Config\Source;

class Places implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 1, 'label'=> __('Catalog/Search')],
            ['value' => 2, 'label'=> __('Product')],
            ['value' => 3, 'label'=> __('Catalog/Search and Product')]
        ];
    }
}