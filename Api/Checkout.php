<?php

namespace LimeSoda\Cashpresso\Api;

use LimeSoda\Cashpresso\Gateway\Config as CashpressoConfig;
use Magento\Framework\Exception\LocalizedException;

class Checkout extends Base
{
    const METHOD_BUY = 'buy';
    const METHOD_SIMULATION = 'simulation/callback';

    const CODE_SIMULATION_SUCCESS = 'SUCCESS';
    const CODE_SIMULATION_CANCEL = 'CANCELLED';
    const CODE_SIMULATION_TIMEOUT = 'TIMEOUT';

    protected $postData;

    /** @var \Magento\Sales\Model\Order $order */
    protected $order;

    public function setVerificationHash($data)
    {
        $account = $this->getConfig()->getTargetAccount();

        $targetAccountId = '';

        if (!empty($account) && $account !== CashpressoConfig::XML_RELOAD_FLAG) {
            $data['targetAccountId'] = $account;
            $targetAccountId = $account;
        }

        $data['verificationHash'] = hash('sha512', $this->getHash($data['amount'], $this->order->getIncrementId(), $targetAccountId));

        return $data;
    }

    public function getContent(): array
    {
        $order = $this->order;
        $price = $this->priceCurrency->round($order->getGrandTotal());

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();

        $data = [
            'partnerApiKey' => $this->getPartnerApiKey(),
            'c2EcomId' => $payment->getData(\Magento\Sales\Api\Data\OrderPaymentInterface::ADDITIONAL_INFORMATION . '/cashpressoToken'),
            'amount' => $price,
            'validUntil' => $this->getConfig()->getTimeout(),
            'bankUsage' => $order->getIncrementId(),
            'interestFreeDaysMerchant' => $this->getConfig()->getInterestFreeDay(),
            'language' => $this->store->getLocale(),
            'callbackUrl' => $this->urlInterface->getUrl('cashpresso/api/callback', ['_secure' => true])
        ];

        $data = $this->setVerificationHash($data);

        if ($customerID = $this->customerSession->getCustomer()->getId()) {
            $data['merchantCustomerId'] = $customerID;
        }

        if ($address = $order->getBillingAddress()) {
            $billingAddress = [
                'country' => $address->getCountryId(),
                'zip' => $address->getPostcode(),
                'city' => $address->getCity(),
                'street' => implode("\n", $address->getStreet())
            ];

            $data['invoiceAddress'] = $billingAddress;
        }

        if ($address = $order->getShippingAddress()) {
            $shippingAddress = [
                'country' => $address->getCountryId(),
                'zip' => $address->getPostcode(),
                'city' => $address->getCity(),
                'street' => implode("\n", $address->getStreet())
            ];

            $data['deliveryAddress'] = $shippingAddress;
        }

        $this->postData = $data;

        return $data;
    }

    /**
     * @param $amount
     * @param $bankUsage
     * @param string $targetAccountId
     * @return string
     * @throws \LimeSoda\Cashpresso\Gateway\Exception
     */
    public function getHash($amount, $bankUsage, $targetAccountId = ''): string
    {
        return $this->getSecretKey() . ';' . ($amount * 100) . ';' . $this->getConfig()->getInterestFreeDay() . ';' . $bankUsage . ';' . $targetAccountId;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return null
     * @throws \LimeSoda\Cashpresso\Gateway\Exception
     * @throws \Zend_Http_Client_Exception
     */
    public function sendOrder($order)
    {
        $this->order = $order;

        /** @var \Magento\Framework\HTTP\ZendClient $request */
        $request = $this->getRequest(Checkout::METHOD_BUY);

        if ($this->getConfig()->isDebugEnabled()) {
            $this->logger->debug(print_r($this->postData, true));
        }

        $response = $request->request();

        if ($this->getConfig()->isDebugEnabled()) {
            $this->logger->debug($response->getBody());
        }

        if ($response->isSuccessful()) {
            $respond = $this->json->deserialize($response->getBody());

            if (is_array($respond)) {
                $respond = $this->handleRespond($respond);

                if (empty($respond['purchaseId'])) {
                    throw new LocalizedException(__(implode(', ', $this->errorMessages)));
                } else {
                    $purchaseId = $respond['purchaseId'];
                }

                return $purchaseId;
            }
        }

        throw new \DomainException(__('cashpresso order request error: %1', $response->getMessage()));
    }
}
