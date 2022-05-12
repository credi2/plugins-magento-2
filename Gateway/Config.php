<?php

namespace LimeSoda\Cashpresso\Gateway;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Webapi\Rest\Request\Deserializer\Json;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\State;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Request\Http;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    const KEY_ACTIVE = 'active';

    const XML_PARTNER_API_KEY = 'payment/cashpresso/api_key';
    const XML_PARTNER_SECRET_KEY = 'payment/cashpresso/secret_key';

    const XML_PARTNER_STATUS = 'status';
    const XML_PARTNER_MODE = 'mode';
    const XML_PARTNER_WIDGET_TYPE = 'widget_type';
    const XML_PARTNER_TEMPLATE = 'template';
    const XML_PARTNER_TIMEOUT = 'timeout';
    const XML_PARTNER_CONTRACT_TEXT = 'sign_contract_text';
    const XML_PARTNER_SUCCESS_TEXT = 'success_text';
    const XML_PARTNER_SUCCESS_BUTTON_TITLE = 'success_button_title';
    const XML_PARTNER_SUCCESS_TITLE = 'success_title';
    const XML_PARTNER_PLACE_TO_SHOW = 'place_to_show';
    const XML_PARTNER_CHECKOUT_BUTTON = 'checkout_button';
    const XML_PARTNER_DEBUG_MODE = 'debug_mode';
    const XML_PARTNER_CHECKOUT_URL = 'checkout_url';
    const XML_PARTNER_TARGET_ACCOUNT = 'account';
    const XML_PARTNER_TARGET_ACCOUNTS = 'account_source';

    const XML_PARTNER_INFO = 'payment/cashpresso/partnerinfo';
    const XML_CASHPRESSO_PRODUCT_TYPES = 'frontend/cashpresso/product_types';

    const XML_PARTNER_INTEREST_FREE_DAYS_MERCHANT = 'interest_free_days_merchant';

    const XML_RELOAD_FLAG = 'reload';

    protected $encryptor;

    protected $date;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    protected $httpRequest;

    protected $json;

    private $groupRepository;

    private $scope = ScopeInterface::SCOPE_STORES;

    private $scopeConfig;

    private $storeId;

    /**
     * LimeSoda Cashpresso config constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * @param Json|null $json
     * @param DateTime $date
     * @param State $state
     * @param Http $httpRequest
     * @param StoreManagerInterface $storeManager
     * @param GroupRepositoryInterface $groupRepository
     * @param null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor,
        Json $json,
        DateTime $date,
        State $state,
        Http $httpRequest,
        StoreManagerInterface $storeManager,
        GroupRepositoryInterface $groupRepository,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    )
    {

        $this->encryptor = $encryptor;
        $this->date = $date;
        $this->state = $state;
        $this->storeManager = $storeManager;
        $this->httpRequest = $httpRequest;
        $this->json = $json;
        $this->groupRepository = $groupRepository;
        $this->scopeConfig = $scopeConfig;
        $this->storeId = $this->setScopeAndStoreId();
        parent::__construct($scopeConfig, $methodCode);
    }

    /**
     * @return int
     */
    private function setScopeAndStoreId()
    {
        if($this->state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML){
            $storeId = (int) $this->httpRequest->getParam('store', 0);
            $websiteId = (int) $this->httpRequest->getParam('website', 0);
            if ($storeId === 0 && $websiteId > 0) {
                try {
                    $groupId = $this->storeManager->getWebsite($websiteId)->getDefaultGroupId();
                    $group = $this->groupRepository->get($groupId);
                    $this->scope = ScopeInterface::SCOPE_WEBSITES;
                    return $group->getDefaultStoreId();
                } catch (\Exception $e) {
                    return 0;
                }
            }else if($storeId === 0 && $websiteId === 0){
                $this->scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            }
        }else {
            $storeId = $this->storeManager->getStore()->getId();
        }

        return $storeId;
    }

    private function getScope(){
        return $this->scope;
    }

    private function getStoreId(){
        return $this->storeId;
    }

    /**
     * @return mixed
     */
    public function getAPIKey()
    {
        return trim($this->scopeConfig->getValue(Config::XML_PARTNER_API_KEY, $this->getScope(), $this->getStoreId()));
    }

    /**
     * @return mixed
     */
    public function getSecretKey()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(Config::XML_PARTNER_SECRET_KEY, $this->getScope(), $this->getStoreId()));
    }

    public function getPartnerInfo()
    {
        $partnerInfo = trim($this->scopeConfig->getValue(Config::XML_PARTNER_INFO, $this->getScope(), $this->getStoreId()));

        return $partnerInfo ? $this->json->deserialize($partnerInfo) : [];
    }

    /**
     * @param null $storeId
     * @return int
     */
    public function getStatus()
    {
        return (int)$this->getValue(Config::XML_PARTNER_STATUS, $this->getStoreId());
    }

    public function getConvertTime($date, $hrs)
    {
        $date = new \Zend_Date($date);
        $date->addHour($hrs);
        return date(DATE_ATOM, $date->getTimestamp());
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getTimeout()
    {
        $hrs = (int)$this->getValue(Config::XML_PARTNER_TIMEOUT, $this->getStoreId());

        return $this->getConvertTime($this->date->timestamp(), $hrs);
    }

    /**
     * 1 - live; 0 - test
     * @param null $storeId
     * @return mixed
     */
    public function getMode()
    {
        return (int)$this->getValue(Config::XML_PARTNER_MODE, $this->getStoreId());
    }

    /**
     *
     * @param null $storeId
     * @return mixed
     */
    public function getWidgetType()
    {
        return (int)$this->getValue(Config::XML_PARTNER_WIDGET_TYPE, $this->getStoreId());
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function getInterestFreeDay()
    {
        if (!$this->getAPIKey()) {
            return false;
        }

        $partnerInfo = $this->getPartnerInfo();

        $customValue = $this->getValue(Config::XML_PARTNER_INTEREST_FREE_DAYS_MERCHANT, $this->getStoreId());

        $cashpressoValue = empty($partnerInfo['interestFreeMaxDuration']) ? 0 : $partnerInfo['interestFreeMaxDuration'];

        return (int) $cashpressoValue && ($customValue > $cashpressoValue) ? $cashpressoValue : $customValue;
    }

    /**
     *
     * @param null $storeId
     * @return mixed
     */
    public function getTemplate()
    {
        return (string)$this->getValue(Config::XML_PARTNER_TEMPLATE, $this->getStoreId());
    }

    /**
     *
     * @param null $storeId
     * @return mixed
     */
    public function getContractText()
    {
        return (string)$this->getValue(Config::XML_PARTNER_CONTRACT_TEXT, $this->getStoreId());
    }

    /**
     *
     * @param null $storeId
     * @return mixed
     */
    public function getSuccessText()
    {
        return (string)$this->getValue(Config::XML_PARTNER_SUCCESS_TEXT, $this->getStoreId());
    }

    /**
     *
     * @param null $storeId
     * @return mixed
     */
    public function getSuccessButtonTitle()
    {
        return (string)$this->getValue(Config::XML_PARTNER_SUCCESS_BUTTON_TITLE, $this->getStoreId());
    }

    /**
     *
     * @param null $storeId
     * @return mixed
     */
    public function getSuccessTitle()
    {
        return (string)$this->getValue(Config::XML_PARTNER_SUCCESS_TITLE, $this->getStoreId());
    }

    /**
     *
     * @param null $storeId
     * @return mixed
     */
    public function getPlaceToShow()
    {
        return (int)$this->getValue(Config::XML_PARTNER_PLACE_TO_SHOW, $this->getStoreId());
    }

    /**
     *
     * @param null $storeId
     * @return mixed
     */
    public function showCheckoutButton()
    {
        return (int)$this->getValue(Config::XML_PARTNER_CHECKOUT_BUTTON, $this->getStoreId());
    }

    /**
     *
     * @param null $storeId
     * @return mixed
     */
    public function getCheckoutUrl()
    {
        return (string)$this->getValue(Config::XML_PARTNER_CHECKOUT_URL, $this->getStoreId());
    }

    /**
     * Gets Payment configuration status.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isActive()
    {
        return (bool)$this->getValue(self::KEY_ACTIVE, $this->getStoreId());
    }

    /**
     * @param bool $useStatus
     * @return bool
     */
    public function checkStatus($useStatus = true)
    {
        return $this->isActive() && (!$useStatus || $this->getStatus()) && ($this->getAPIKey());
    }

    /**
     * @return mixed
     */
    public function isDebugEnabled()
    {
        return (bool)$this->getValue(self::XML_PARTNER_DEBUG_MODE, $this->getStoreId());
    }

    /**
     * @return mixed
     */
    public function getTargetAccount()
    {
        return $this->getValue(self::XML_PARTNER_TARGET_ACCOUNT, $this->getStoreId());
    }

    /**
     * @return mixed
     */
    public function getTargetAccounts()
    {
        return $this->getValue(self::XML_PARTNER_TARGET_ACCOUNTS);
    }

    /**
     * @return null
     */
    public function getTotalLimit()
    {
        $partnerInfo = $this->getPartnerInfo();

        return empty($partnerInfo['limit']['total']) ? null : $partnerInfo['limit']['total'];
    }

    /**
     * @return null
     */
    public function getPaybackRate()
    {
        $partnerInfo = $this->getPartnerInfo();

        return empty($partnerInfo['paybackRate']) ? null : $partnerInfo['paybackRate'];
    }

    /**
     * @return null
     */
    public function getMinPaybackAmount()
    {
        $partnerInfo = $this->getPartnerInfo();

        return empty($partnerInfo['minPaybackAmount']) ? null : $partnerInfo['minPaybackAmount'];
    }

    /**
     * @return null
     */
    public function getContractCurrency()
    {
        $partnerInfo = $this->getPartnerInfo();

        return empty($partnerInfo['currency']) ? null : $partnerInfo['currency'];
    }

    protected function _getDomain()
    {
        return 'https://' . ($this->getMode() ? 'my.cashpresso.com' : 'my.test-cashpresso.com') . '/';
    }

    /**
     * @return string
     */
    public function getJsLabelScript()
    {
        $scriptStatic = !$this->getWidgetType() ? '_static' : '';

        $jsSrc = $this->_getDomain() . 'ecommerce/v2/label/c2_ecom_wizard' . $scriptStatic . '.all.min.js';

        return $jsSrc;
    }

    /**
     * @return string
     */
    public function getJsCheckoutScript()
    {
        $jsSrc = $this->_getDomain() . 'ecommerce/v2/checkout/c2_ecom_checkout.all.min.js';

        return $jsSrc;
    }

    /**
     * @return string
     */
    public function getJsPostCheckoutScript()
    {
        $jsSrc = $this->_getDomain() . 'ecommerce/v2/checkout/c2_ecom_post_checkout.all.min.js';

        return $jsSrc;
    }

    /**
     * @param $price
     * @param null $params
     * @return float|int
     */
    public function getDebt($price, $params = null)
    {
        $partnerInfo = $params ? $params : $this->getPartnerInfo();

        $minPayment = 0;

        if (isset($partnerInfo['minPaybackAmount']) && isset($partnerInfo['paybackRate'])) {
            $minPayment = round(min($price, max($partnerInfo['minPaybackAmount'],
                $price * 0.01 * $partnerInfo['paybackRate'])), 2);
        }

        return $minPayment;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCurrentStore()
    {
        $website_id = null;

        if ($this->state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
            $storeId = (int) $this->httpRequest->getParam('store', 0);
        } else {
            $storeId = true; // get current store from the store resolver
        }

        $store = $this->storeManager->getStore($storeId);
        $website_id = $store->getWebsiteId();

        return [$website_id, $storeId];
    }

    /**
     * Instructions text
     *
     * @var string
     */
    protected $_instructions;

    /**
     * Get instructions text from config
     *
     * @return null|string
     */
    public function getInstructions()
    {
        if ($this->_instructions === null) {
            /** @var \Magento\Payment\Model\Method\AbstractMethod $method */
            $method = $this->getMethod();
            $this->_instructions = $method->getConfigData('instructions');
        }
        return $this->_instructions;
    }
}
