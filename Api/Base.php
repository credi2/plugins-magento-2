<?php

namespace LimeSoda\Cashpresso\Api;

use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Curl;
use Laminas\Http\Request;
use LimeSoda\Cashpresso\Gateway\Config;
use Magento\Framework\App\Action\Context;
use LimeSoda\Cashpresso\Logger\Logger;
use Magento\Framework\DataObject;
use Magento\Framework\Phrase;
use Magento\Framework\Webapi\Rest\Request\Deserializer\Json;
use LimeSoda\Cashpresso\Helper\Store;
use Magento\Framework\UrlInterface;
use LimeSoda\Cashpresso\Model\CustomerSession;
use Magento\Framework\Pricing\PriceCurrencyInterface;

abstract class Base
{
    const CP_ERROR_MULTIPLE_ERRORS = 'MULTIPLE_ERRORS';
    const CP_ERROR_INVALID_INPUT = 'INVALID_INPUT';
    const CP_ERROR_LIMIT_EXCEEDED = 'LIMIT_EXCEEDED';
    const CP_ERROR_INVALID_ZIP = 'INVALID_ZIP';
    const CP_ERROR_UNPARSABLE = 'UNPARSABLE';
    const CP_ERROR_VERIFICATION_FAILED = 'VERIFICATION_FAILED';
    const CP_ERROR_VERIFICATION_TIMEOUT = 'VERIFICATION_TIMEOUT';
    const CP_ERROR_INVALID_STATE = 'INVALID_STATE';
    const CP_ERROR_DUPLICATE_PHONE = 'DUPLICATE_PHONE';
    const CP_ERROR_DUPLICATE_IBAN = 'DUPLICATE_IBAN';
    const CP_ERROR_DUPLICATE_EMAIL = 'DUPLICATE_EMAIL';
    const CP_ERROR_INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';
    const CP_ERROR_DUPLICATE_CUSTOMER = 'DUPLICATE_CUSTOMER';

    const DEV_URL = 'https://backend.dev-cashpresso.com/backend/ecommerce/v2/';
    const TEST_URL = 'https://backend.test-cashpresso.com/backend/ecommerce/v2/';
    const LIVE_URL = 'https://rest.cashpresso.com/backend/ecommerce/v2/';

    /**
     * JSON HTTP Content-Type Header.
     *
     * @var string
     */
    private static $jsonDataType = 'application/json';

    /**
     * @var
     */
    protected $config;

    protected $context;

    protected $messageManager;

    protected $logger;

    protected $client;

    protected $dataObject;

    protected $json;

    protected $store;

    protected $urlInterface;

    protected $customerSession;

    protected $priceCurrency;

    protected $errorMessages = [];

    public function __construct(Config $config,
                                Context $context,
                                Logger $logger,
                                Client $client,
                                DataObject $dataObject,
                                Json $json,
                                Store $store,
                                UrlInterface $urlInterface,
                                CustomerSession $customerSession,
                                PriceCurrencyInterface $priceCurrencyInterface
)
    {
        $this->config = $config;
        $this->context = $context;
        $this->logger = $logger;
        $this->client = $client;
        $this->dataObject = $dataObject;
        $this->json = $json;
        $this->store = $store;
        $this->urlInterface = $urlInterface;
        $this->customerSession = $customerSession;
        $this->priceCurrency = $priceCurrencyInterface;

        $this->messageManager = $context->getMessageManager();
    }

    abstract function getContent(): array;

    /**
     * @return Config
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * @param $respond
     * @return bool|array
     */
    protected function handleRespond($respond)
    {
        if (empty($respond['success'])) {
            $errors = $respond['errors'] ?? [$respond['error']];

            foreach ($errors as $error) {
                if (!empty($error['type']) && $this->handleError($error['type'])) {
                    $this->messageManager
                        ->addWarningMessage(
                            __($this->handleError($error['type'])) . ' - ' . $error['description']
                        );
                    $this->errorMessages[] = $this->handleError($error['type']) . ' - ' . $error['description'];
                }
            }

            return false;
        }

        return $respond;
    }

    /**
     * @param $code
     * @return Phrase|null
     */
    public function handleError($code)
    {
        $message = null;

        switch ($code) {
            case self::CP_ERROR_MULTIPLE_ERRORS:
                $message = __('cashpresso: Multiple validation errors - check errors element for details');
                break;
            case self::CP_ERROR_INVALID_INPUT:
                $message = __('cashpresso: Input is invalid or malformed');
                break;
            case self::CP_ERROR_LIMIT_EXCEEDED:
                $message = __('cashpresso: The amount or necessary prepayment is too high');
                break;
            case self::CP_ERROR_INVALID_ZIP:
                $message = __('cashpresso: Zip address validation failed');
                break;
            case self::CP_ERROR_UNPARSABLE:
                $message = __('cashpresso: Input cannot be parsed - format error');
                break;
            case self::CP_ERROR_VERIFICATION_FAILED:
                $message = __('cashpresso: Verification failed (e.g. of TAN code)');
                break;
            case self::CP_ERROR_VERIFICATION_TIMEOUT:
                $message = __('cashpresso: Verification failed due to timeout (e.g. TAN timeout)');
                break;
            case self::CP_ERROR_INVALID_STATE:
                $message = __('cashpresso: Operation is not allowed in current state');
                break;
            case self::CP_ERROR_DUPLICATE_PHONE:
                $message = __('cashpresso: Customer with phone number already exists');
                break;
            case self::CP_ERROR_DUPLICATE_IBAN:
                $message = __('cashpresso: Customer with iban already exists');
                break;
            case self::CP_ERROR_DUPLICATE_EMAIL:
                $message = __('cashpresso: Customer with email already exists');
                break;
            case self::CP_ERROR_INTERNAL_SERVER_ERROR:
                $message = __('cashpresso: Customer with this identity (name, birthdate, etc..) already exists');
                break;
            case self::CP_ERROR_DUPLICATE_CUSTOMER:
                $message = __('cashpresso: Unexpected error - please contact your account manager');
                break;
        }

        return $message;
    }

    /**
     * @return mixed
     */
    public function getPartnerApiKey()
    {
        return $this->getConfig()->getAPIKey();
    }

    /**
     * @return mixed
     */
    public function getSecretKey()
    {
        return $this->getConfig()->getSecretKey();
    }

    public function getUrl()
    {
        switch ($this->getConfig()->getMode()) {
            case -1:
                return self::DEV_URL;
            case 0:                
                return self::TEST_URL;
            default:
                return self::LIVE_URL;
        }
    }

    protected function getDataObject()
    {
        return clone $this->dataObject;
    }

    /**
     * @see like it was done here
     * src/vendor/magento/module-signifyd/Model/SignifydGateway/Client/HttpClientFactory.php
     *
     * @param $method
     * @return Client
     */
    public function getRequest($method)
    {
        $partnerInfoObject = $this->getDataObject();

        $data = $this->getContent();

        if ($this->getConfig()->isDebugEnabled()){
            $this->logger->debug($this->getUrl() . $method);
            $this->logger->debug(print_r($data, true));
        }

        $partnerInfoObject->addData($data);

        $content = $partnerInfoObject->toJson();

        $this->client->setAdapter(Curl::class);
        $this->client->setUri($this->getUrl() . $method);
        $this->client->setRawBody($content);
        $this->client->setMethod(Request::METHOD_POST);
        $this->client->setEncType(self::$jsonDataType);

        return $this->client;
    }
}
