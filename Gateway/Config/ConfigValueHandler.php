<?php

namespace LimeSoda\Cashpresso\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Webapi\Rest\Request\Deserializer\Json;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\State;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Config\ValueHandlerInterface;

/**
 * Default implementation of config value handler.
 *
 * This class is designed to be injected into other classes. Inheritance in not recommended.
 *
 * @api
 * @since 100.0.2
 */
class ConfigValueHandler implements ValueHandlerInterface
{
    /**
     * @var \LimeSoda\Cashpresso\Gateway\Config
     */
    protected $configInterface;

    /**
     * @param \Magento\Payment\Gateway\ConfigInterface $configInterface
     */
    public function __construct(
        ConfigInterface $configInterface
    ) {
        $this->configInterface = $configInterface;
    }

    /**
     * @param array $subject
     * @param null $storeId
     * @return array|bool|mixed|null
     * @throws \LimeSoda\Cashpresso\Gateway\Exception
     */
    public function handle(array $subject, $storeId = null)
    {
        switch (SubjectReader::readField($subject)){
            case 'partnerinfo':
                $value = $this->configInterface->getPartnerInfo();
                break;
            case 'interestfreeday':
                $value = $this->configInterface->getInterestFreeDay();
                break;
            case 'totallimit':
                $value = $this->configInterface->getTotalLimit();
                break;
            case 'min_limit':
                $value = $this->configInterface->getMinPaybackAmount();
                break;
            case 'contractcurrency':
                $value = $this->configInterface->getContractCurrency();
                break;
            case 'timeout':
                $value = $this->configInterface->getTimeout();
                break;
            case 'debt':
                $value = $this->configInterface->getDebt();
                break;
        }

        return $value ?? $this->configInterface->getValue(SubjectReader::readField($subject), $storeId);
    }
}