<?php

namespace LimeSoda\Cashpresso\Block\Adminhtml\System\Config\Form\Field;

use LimeSoda\Cashpresso\Gateway\Config;
use LimeSoda\Cashpresso\Helper\Store;

class Currency extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $csConfig;

    protected $request;

    protected $storeHelper;

    public function __construct(\Magento\Backend\Block\Template\Context $context,
                                Config $config,
                                Store $store,
                                array $data = [])
    {
        parent::__construct($context, $data);

        $this->csConfig = $config;
        $this->request = $context->getRequest();
        $this->storeHelper = $store;
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $text = __('cashpresso account currency (%1) does not match to store currency (%2).', $this->csConfig->getContractCurrency(), $this->storeHelper->getStoreCurrency());

        if ($this->checkCurrency() === false) {
            return '<div id="' . $element->getHtmlId() . '">' .
                $text . '</div>' . $element->getAfterElementHtml();
        }

        return '';
    }

    /**
     * @return bool|null
     */
    protected function checkCurrency()
    {
        if (!$contractCurrency = $this->csConfig->getContractCurrency()) {
            return null;
        }

        return $this->storeHelper->getStoreCurrency() == $contractCurrency;
    }

    /**
     * Decorate field row html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @param string $html
     * @return string
     */
    protected function _decorateRowHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element, $html)
    {
        if ($this->checkCurrency() === false) {
            return '<tr id="row_' . $element->getHtmlId() . '">' . $html . '</tr>';
        }

        return '';
    }
}
