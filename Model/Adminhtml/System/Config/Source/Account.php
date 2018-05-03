<?php


namespace LimeSoda\Cashpresso\Model\Adminhtml\System\Config\Source;

use LimeSoda\Cashpresso\Api\Account as AccountAPI;

class Account implements \Magento\Framework\Option\ArrayInterface
{

    private $accountApi;

    public function __construct(AccountAPI $accountApi)
    {
        $this->accountApi = $accountApi;
    }


    public function getTargetAccounts()
    {
        return $this->accountApi->getTargetAccounts();
    }


    /**
     * @return array|void
     */
    public function toOptionArray()
    {
        $accounts = $this->getTargetAccounts();

        $list = array();

        foreach ($accounts as $account) {
            $list[] = array(
                'value' => $account['targetAccountId'],
                'label' => $account['holder']
            );
        }

        array_unshift($list, array('value' => '', 'label' => __('-- Not Selected --')));

        return $list;
    }

    /**⁄
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $accounts = $this->getTargetAccounts();

        $list = array();

        foreach ($accounts as $account) {
            $list[$account['targetAccountId']] = $account['holder'];
        }

        array_unshift($list, array('' => __('-- Not Selected --')));

        return $list;
    }
}