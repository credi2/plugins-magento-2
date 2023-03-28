<?php

namespace LimeSoda\Cashpresso\Gateway;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Exception;
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

    protected EncryptorInterface $encryptor;

    protected DateTime $date;

    /**
     * @var State
     */
    protected State $state;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    protected Http $httpRequest;

    protected Json $json;

    private GroupRepositoryInterface $groupRepository;

    private $scope = ScopeInterface::SCOPE_STORES;

    private ScopeConfigInterface $scopeConfig;

    private int $storeId;

    /**
     * LimeSoda Cashpresso config constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * @param Json $json
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
    ) {
        $this->encryptor = $encryptor;
        $this->date = $date;
        $this->state = $state;
        $this->storeManager = $storeManager;
        $this->httpRequest = $httpRequest;
        $this->json = $json;
        $this->groupRepository = $groupRepository;
        $this->scopeConfig = $scopeConfig;
        $this->storeId = (int) $this->setScopeAndStoreId();
        parent::__construct($scopeConfig, $methodCode);
    }

    /**
     * @return int
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    private function setScopeAndStoreId()
    {
        if($this->state->getAreaCode() == Area::AREA_ADMINHTML){
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

    /**
     * @return string
     */
    private function getScope(){
        return $this->scope;
    }

    /**
     * @return int
     */
    private function getStoreId(){
        return $this->storeId;
    }

    /**
     * @return string|null
     */
    public function getAPIKey()
    {
        $value = $this->scopeConfig->getValue(Config::XML_PARTNER_API_KEY, $this->getScope(), $this->getStoreId());
        return is_string($value) ? trim($value) : null;
    }

    /**
     * @return string
     */
    public function getSecretKey()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(Config::XML_PARTNER_SECRET_KEY, $this->getScope(), $this->getStoreId()));
    }

    /**
     * @return array|null
     * @throws Exception
     */
    public function getPartnerInfo()
    {
        $value = $this->scopeConfig->getValue(Config::XML_PARTNER_INFO, $this->getScope(), $this->getStoreId());
        $partnerInfo = is_string($value) ? trim($value) : null;
        return $partnerInfo ? $this->json->deserialize($partnerInfo) : [];
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return (int)$this->getValue(Config::XML_PARTNER_STATUS, $this->getStoreId());
    }

    /**
     * @param $date
     * @param $hrs
     * @return string
     */
    public function getConvertTime($date, $hrs)
    {
        $date = new \Zend_Date($date);
        $date->addHour($hrs);
        return date(DATE_ATOM, $date->getTimestamp());
    }

    /**
     * @return string
     */
    public function getTimeout()
    {
        $hrs = (int)$this->getValue(Config::XML_PARTNER_TIMEOUT, $this->getStoreId());

        return $this->getConvertTime($this->date->timestamp(), $hrs);
    }

    /**
     * 1 - live; 0 - test
     * @return int
     */
    public function getMode()
    {
        return (int)$this->getValue(Config::XML_PARTNER_MODE, $this->getStoreId());
    }

    /**
     * @return int
     */
    public function getWidgetType()
    {
        return (int)$this->getValue(Config::XML_PARTNER_WIDGET_TYPE, $this->getStoreId());
    }

    /**
     * @return bool
     * @throws Exception
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
     * @return string
     */
    public function getTemplate()
    {
        return (string)$this->getValue(Config::XML_PARTNER_TEMPLATE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function getContractText()
    {
        return (string)$this->getValue(Config::XML_PARTNER_CONTRACT_TEXT, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function getSuccessText()
    {
        return (string)$this->getValue(Config::XML_PARTNER_SUCCESS_TEXT, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function getSuccessButtonTitle()
    {
        return (string)$this->getValue(Config::XML_PARTNER_SUCCESS_BUTTON_TITLE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function getSuccessTitle()
    {
        return (string)$this->getValue(Config::XML_PARTNER_SUCCESS_TITLE, $this->getStoreId());
    }

    /**
     * @return int
     */
    public function getPlaceToShow()
    {
        return (int)$this->getValue(Config::XML_PARTNER_PLACE_TO_SHOW, $this->getStoreId());
    }

    /**
     * @return int
     */
    public function showCheckoutButton()
    {
        return (int)$this->getValue(Config::XML_PARTNER_CHECKOUT_BUTTON, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function getCheckoutUrl()
    {
        return (string)$this->getValue(Config::XML_PARTNER_CHECKOUT_URL, $this->getStoreId());
    }

    /**
     * Gets Payment configuration status.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool)$this->getValue(self::KEY_ACTIVE, $this->getStoreId());
    }

    /**
     * @param bool $useStatus
     * @return bool
     */
    public function checkStatus($useStatus = true): bool
    {
        return $this->isActive() && (!$useStatus || $this->getStatus()) && ($this->getAPIKey());
    }

    /**
     * @return bool
     */
    public function isDebugEnabled(): bool
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
     * @throws Exception
     */
    public function getTotalLimit()
    {
        $partnerInfo = $this->getPartnerInfo();

        return empty($partnerInfo['limit']['total']) ? null : $partnerInfo['limit']['total'];
    }

    /**
     * @return null
     * @throws Exception
     */
    public function getPaybackRate()
    {
        $partnerInfo = $this->getPartnerInfo();

        return empty($partnerInfo['paybackRate']) ? null : $partnerInfo['paybackRate'];
    }

    /**
     * @return null
     * @throws Exception
     */
    public function getMinPaybackAmount()
    {
        $partnerInfo = $this->getPartnerInfo();

        return empty($partnerInfo['minPaybackAmount']) ? null : $partnerInfo['minPaybackAmount'];
    }

    /**
     * @return null
     * @throws Exception
     */
    public function getContractCurrency()
    {
        $partnerInfo = $this->getPartnerInfo();

        return empty($partnerInfo['currency']) ? null : $partnerInfo['currency'];
    }

    /**
     * @return string
     */
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
        return $this->_getDomain() . 'ecommerce/v2/label/c2_ecom_wizard' . $scriptStatic . '.all.min.js';
    }

    /**
     * @return string
     */
    public function getJsCheckoutScript(): string
    {
        return $this->_getDomain() . 'ecommerce/v2/checkout/c2_ecom_checkout.all.min.js';
    }

    /**
     * @return string
     */
    public function getJsPostCheckoutScript(): string
    {
        return $this->_getDomain() . 'ecommerce/v2/checkout/c2_ecom_post_checkout.all.min.js';
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
     * @throws LocalizedException
     */
    public function getCurrentStore()
    {
        $website_id = null;

        if ($this->state->getAreaCode() == Area::AREA_ADMINHTML) {
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
