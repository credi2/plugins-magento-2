<?php

namespace LimeSoda\Cashpresso\Block\Adminhtml\System\Config\Form\Field;

use LimeSoda\Cashpresso\Gateway\Config;
use LimeSoda\Cashpresso\Helper\Store;

class Information extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $csConfig;

    protected $request;
    protected $store;

    public function __construct(\Magento\Backend\Block\Template\Context $context,
                                Config $config,
                                Store $storeHelper,
                                array $data = [])
    {

        parent::__construct($context, $data);

        $this->csConfig = $config;
        $this->request = $context->getRequest();
        $this->store = $storeHelper;
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $text = '';
        $currentStoreId = $this->store->getCurrentStoredId();
        if (!$this->csConfig->getAPIKey($currentStoreId)) {
            return __('Please, enter the Partner API Key.');
        }

        $partnerInfo = $this->csConfig->getPartnerInfo($currentStoreId);

        if (!empty($partnerInfo) && is_array($partnerInfo) && !empty($partnerInfo['success'])) {
            $list = [];

            if (isset($partnerInfo['companyName'])) {
                $list[] = [
                    'title' => __('Company name'),
                    'value' => $partnerInfo['companyName']
                ];
            }

            if (isset($partnerInfo['email'])) {
                $list[] = [
                    'title' => __('Email'),
                    'value' => $partnerInfo['email']
                ];
            }

            if (isset($partnerInfo['holder'])) {
                $list[] = [
                    'title' => __('Holder'),
                    'value' => $partnerInfo['holder']
                ];
            }

            if (isset($partnerInfo['iban'])) {
                $list[] = [
                    'title' => __('Iban'),
                    'value' => $partnerInfo['iban']
                ];
            }

            if (isset($partnerInfo['interestFreeEnabled'])) {
                $list[] = [
                    'title' => __('Interest Free Status'),
                    'value' => (bool)$partnerInfo['interestFreeEnabled']
                ];
            }

            if (isset($partnerInfo['interestFreeMaxDuration'])) {
                $list[] = [
                    'title' => __('Interest Free Max Duration'),
                    'value' => (bool)$partnerInfo['interestFreeMaxDuration']
                ];
            }

            if (isset($partnerInfo['status'])) {
                $list[] = [
                    'title' => __('Status'),
                    'value' => $partnerInfo['status']
                ];
            }

            if (isset($partnerInfo['currency'])) {
                $list[] = [
                    'title' => __('Currency'),
                    'value' => $partnerInfo['currency']
                ];
            }

            if (isset($partnerInfo['minPaybackAmount'])) {
                $list[] = [
                    'title' => __('Minimal payback amount'),
                    'value' => $partnerInfo['minPaybackAmount']
                ];
            }

            if (isset($partnerInfo['paybackRate'])) {
                $list[] = [
                    'title' => __('Payback rate'),
                    'value' => $partnerInfo['paybackRate']
                ];
            }

            if (isset($partnerInfo['limit']['financing'])) {
                $list[] = [
                    'title' => __('Financing limit'),
                    'value' => (int)$partnerInfo['limit']['financing']
                ];
            }

            if (isset($partnerInfo['limit']['prepayment'])) {
                $list[] = [
                    'title' => __('Prepayment limit'),
                    'value' => (int)$partnerInfo['limit']['prepayment']
                ];
            }

            if (isset($partnerInfo['limit']['total'])) {
                $list[] = [
                    'title' => __('Total limit'),
                    'value' => (int)$partnerInfo['limit']['total']
                ];
            }

            if (isset($partnerInfo['interest']['nominal'])) {
                $list[] = [
                    'title' => __('Interest nominal'),
                    'value' => $partnerInfo['interest']['nominal']['min']
                        . " - " . $partnerInfo['interest']['nominal']['max']
                ];
            }

            if (isset($partnerInfo['interest'])) {
                $list[] = [
                    'title' => __('Interest effective'),
                    'value' => $partnerInfo['interest']['effective']['min']
                        . " - " . $partnerInfo['interest']['effective']['max']
                ];
            }

            if (isset($partnerInfo['interestFreeCashpresso'])) {
                $list[] = [
                    'title' => __('Interest Free cashpresso'),
                    'value' => (int)$partnerInfo['interestFreeCashpresso']
                ];
            }

            if (isset($partnerInfo['last_update'])) {
                $list[] = [
                    'title' => __('Last Update'),
                    'value' => $partnerInfo['last_update']
                ];
            }

            foreach ($list as $item) {
                $text .= "<p><label style='font-weight: bold'>{$item['title']}:</label> {$item['value']}</p>";
            }
        }

        return $text;
    }
}
