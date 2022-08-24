<?php

namespace LimeSoda\Cashpresso\Model\Config\Backend;

use LimeSoda\Cashpresso\Api\Account as AccountAPI;
use LimeSoda\Cashpresso\Gateway\Config as CsConfig;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use LimeSoda\Cashpresso\Api\Account as ApiAccount;
use LimeSoda\Cashpresso\Gateway\Config;

class Account extends \Magento\Framework\App\Config\Value
{
    protected ReinitableConfigInterface $appConfig;
    protected Config $csConfig;
    protected ApiAccount $accountApi;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param MutableScopeConfigInterface $mutableConfig
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        MutableScopeConfigInterface $mutableConfig,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        ApiAccount $account,
        Config $csConfig,
        ReinitableConfigInterface $appConfig,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);

        $this->csConfig = $csConfig;
        $this->accountApi = $account;
        $this->appConfig = $appConfig;
    }

    /**
     * @return $this|\Magento\Framework\App\Config\Value
     */
    public function beforeSave()
    {
        if ($this->csConfig->isDebugEnabled() &&
            $this->getData('fieldset_data/active') &&
            $this->getData('fieldset_data/api_key') &&
            $this->getData('fieldset_data/secret_key')) {
                if ($this->getValue() == CsConfig::XML_RELOAD_FLAG) {
                    $this->setValue($this->csConfig->getTargetAccount() ?? null);
                }
        }

        return $this;
    }
}
