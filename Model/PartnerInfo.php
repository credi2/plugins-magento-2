<?php

namespace LimeSoda\Cashpresso\Model;

use LimeSoda\Cashpresso\Gateway\Config;
use LimeSoda\Cashpresso\Api\Info;
use LimeSoda\Cashpresso\Helper\Store;
use LimeSoda\Cashpresso\Model\Ui\ConfigProvider;
use Magento\Config\Model\ResourceModel\Config as SystemConfig;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\DataObject;
use Magento\Config\Model\Config\Factory;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Stdlib\DateTime\Timezone;

class PartnerInfo
{
    protected $csConfig;

    protected $client;

    protected $dataObject;

    protected $configFactory;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $salesOrderConfig;

    /**
     * @var \Magento\Payment\Model\Config
     */
    protected $paymentConfig;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     * Application config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $appConfig;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    protected $store;

    protected WriterInterface $configWriter;

    public function __construct(
        Config                    $config,
        Info                      $client,
        SystemConfig              $resourceConfig,
        DataObject                $dataObject,
        Factory                   $configFactory,
        ReinitableConfigInterface $appConfig,
        Timezone                  $timezone,
        Store                     $storeHelper,
        WriterInterface           $configWriter
    )
    {
        $this->csConfig = $config;

        $this->client = $client;

        $this->resourceConfig = $resourceConfig;

        $this->dataObject = $dataObject;

        $this->configFactory = $configFactory;

        $this->appConfig = $appConfig;

        $this->timezone = $timezone;

        $this->store = $storeHelper;

        $this->configWriter = $configWriter;
    }

    public function generatePartnerInfo($scopeId = 0, $scope = 'default')
    {
        $partnerInfo = $this->client->getPartnerInfo();

        if (is_array($partnerInfo)) {

            $this->dataObject->setData($partnerInfo);

            $this->dataObject->addData(array(
                'last_update' => $this->timezone->formatDate(null, \IntlDateFormatter::SHORT, true)
            ));

            $this->configWriter->save(
                'payment/' . ConfigProvider::CODE . '/partnerinfo',
                $this->dataObject->toJson(),
                $scope,
                $scopeId
            );

            $this->appConfig->reinit();
        }
    }

    public function removePartnerInfo($scopeId = 0, $scope = 'default')
    {
        $this->configWriter->delete('payment/' . ConfigProvider::CODE . '/partnerinfo', $scope, $scopeId);
        $this->appConfig->reinit();
    }
}
