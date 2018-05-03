<?php


namespace LimeSoda\Cashpresso\Block\Adminhtml\System\Config\Form\Field;

use LimeSoda\Cashpresso\Gateway\Config;

class Information extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $csConfig;

    protected $request;
    
    public function __construct(\Magento\Backend\Block\Template\Context $context,
                                Config $config,
                                array $data = [])
    {
        
        parent::__construct($context, $data);

        $this->csConfig = $config;
        $this->request = $context->getRequest();
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $text = '';
        
        if (!$this->csConfig->getAPIKey()) {
            return __('Please, enter the Partner API Key.');
        }

        $partnerInfo = $this->csConfig->getPartnerInfo();

        if (!empty($partnerInfo) && is_array($partnerInfo) && !empty($partnerInfo['success'])) {
            $list = array();

            if (isset($partnerInfo['companyName'])) {
                $list[] = array(
                    'title' => __('Company name'),
                    'value' => $partnerInfo['companyName']);
            }

            if (isset($partnerInfo['email'])) {
                $list[] = array(
                    'title' => __('Email'),
                    'value' => $partnerInfo['email']);
            }

            if (isset($partnerInfo['holder'])) {
                $list[] = array(
                    'title' => __('Holder'),
                    'value' => $partnerInfo['holder']
                );
            }

            if (isset($partnerInfo['iban'])) {
                $list[] = array(
                    'title' => __('Iban'),
                    'value' => $partnerInfo['iban']
                );
            }

            if (isset($partnerInfo['interestFreeEnabled'])) {
                $list[] = array(
                    'title' => __('Interest Free Status'),
                    'value' => (bool)$partnerInfo['interestFreeEnabled']
                );
            }

            if (isset($partnerInfo['interestFreeMaxDuration'])) {
                $list[] = array(
                    'title' => __('Interest Free Max Duration'),
                    'value' => (bool)$partnerInfo['interestFreeMaxDuration']
                );
            }

            if (isset($partnerInfo['status'])) {
                $list[] = array(
                    'title' => __('Status'),
                    'value' => $partnerInfo['status']
                );
            }

            if (isset($partnerInfo['currency'])) {
                $list[] = array(
                    'title' => __('Currency'),
                    'value' => $partnerInfo['currency']
                );
            }

            if (isset($partnerInfo['minPaybackAmount'])) {
                $list[] = array(
                    'title' => __('Minimal payback amount'),
                    'value' => $partnerInfo['minPaybackAmount']
                );
            }

            if (isset($partnerInfo['paybackRate'])) {
                $list[] = array(
                    'title' => __('Payback rate'),
                    'value' => $partnerInfo['paybackRate']
                );
            }

            if (isset($partnerInfo['limit']['financing'])) {
                $list[] = array(
                    'title' => __('Financing limit'),
                    'value' => (int)$partnerInfo['limit']['financing']
                );
            }

            if (isset($partnerInfo['limit']['prepayment'])) {
                $list[] = array(
                    'title' => __('Prepayment limit'),
                    'value' => (int)$partnerInfo['limit']['prepayment']
                );
            }

            if (isset($partnerInfo['limit']['total'])) {
                $list[] = array(
                    'title' => __('Total limit'),
                    'value' => (int)$partnerInfo['limit']['total']
                );
            }

            if (isset($partnerInfo['interest']['nominal'])) {
                $list[] = array(
                    'title' => __('Interest nominal'),
                    'value' => $partnerInfo['interest']['nominal']['min']
                        . " - " . $partnerInfo['interest']['nominal']['max']
                );
            }

            if (isset($partnerInfo['interest'])) {
                $list[] = array(
                    'title' => __('Interest effective'),
                    'value' => $partnerInfo['interest']['effective']['min']
                        . " - " . $partnerInfo['interest']['effective']['max']
                );
            }

            if (isset($partnerInfo['interestFreeCashpresso'])) {
                $list[] = array(
                    'title' => __('Interest Free cashpresso'),
                    'value' => (int)$partnerInfo['interestFreeCashpresso']
                );
            }

            if (isset($partnerInfo['last_update'])) {
                $list[] = array(
                    'title' => __('Last Update'),
                    'value' => $partnerInfo['last_update']
                );
            }

            foreach ($list as $item) {
                $text .= "<p><label style='font-weight: bold'>{$item['title']}:</label> {$item['value']}</p>";
            }
        }

        return $text;
    }
}