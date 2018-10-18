<?php

namespace LimeSoda\Cashpresso\Model\Adminhtml\System\Config\Source;

use LimeSoda\Cashpresso\Gateway\Config;
use Magento\Framework\Serialize\Serializer\Serialize;

class Account implements \Magento\Framework\Option\ArrayInterface
{
    protected $accountApi;

    protected $config;

    protected $serialize;

    public function __construct(
        Config $config,
        Serialize $serialize)
    {
        $this->config = $config;
        $this->serialize = $serialize;
    }

    /**
     * @return array|void
     */
    public function toOptionArray()
    {

        $accounts = $this->getTargetAccounts();

        $list = [];

        foreach ($accounts as $account) {
            $list[] = [
                'value' => $account['targetAccountId'],
                'label' => $account['holder']
            ];
        }

        array_unshift($list, ['value' => '', 'label' => __('-- Not Selected --')]);

        return $list;
    }

    /**
     * @return array
     */
    public function getTargetAccounts()
    {
        try {
            $accounts = $this->serialize->unserialize($this->config->getTargetAccounts());
        } catch (\Exception $e) {
            $accounts = [];
        }


        $default = [
            [
                'targetAccountId' => Config::XML_RELOAD_FLAG,
                'holder' => __('Update the list of target accounts')
            ]
        ];

        return array_merge($default, $accounts);
    }

    /**⁄
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $accounts = $this->getTargetAccounts();

        $list = [];

        foreach ($accounts as $account) {
            $list[$account['targetAccountId']] = $account['holder'];
        }

        array_unshift($list, ['' => __('-- Not Selected --')]);

        return $list;
    }
}