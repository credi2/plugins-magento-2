<?php

namespace LimeSoda\Cashpresso\Api;

class Checkout extends Base
{
    const METHOD_BUY = 'buy';
    const METHOD_SIMULATION = 'simulation/callback';

    const CODE_SIMULATION_SUCCESS = 'SUCCESS';
    const CODE_SIMULATION_CANCEL = 'CANCELLED';
    const CODE_SIMULATION_TIMEOUT = 'TIMEOUT';

    protected $postData;
    protected $order;

    public function getContent()
    {
        /** @var \Magento\Sales\Model\Order $order */
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

        if (!empty($account = $this->getConfig()->getTargetAccount())) {
            $data['targetAccountId'] = $account;
        }

        $data['verificationHash'] = hash('sha512', $this->getHash($price, $order->getIncrementId(), $account));

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

        $items = $order->getAllItems();

        $cart = [];

        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($items as $item) {
            $cart[] = [
                'description' => $item->getName(),
                'amount' => $item->getQtyOrdered()
            ];
        }

        $this->postData = $data;

        return $data;
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
                    throw new \DomainException(__('cashpresso: purchaseId is empty'));

                    $purchaseId = null;
                } else {
                    $purchaseId = $respond['purchaseId'];
                }

                return $purchaseId;
            }
        }

        throw new \DomainException(__('cashpresso order request error: %1', $response->getMessage()));
    }

    /**
     * @param $amount
     * @param $bankUsage
     * @param string $targetAccountId
     * @return string
     * @throws \LimeSoda\Cashpresso\Gateway\Exception
     */
    public function getHash($amount, $bankUsage, $targetAccountId = '')
    {
        return $this->getSecretKey() . ';' . ($amount * 100) . ';' . $this->getConfig()->getInterestFreeDay() . ';' . $bankUsage . ';' . $targetAccountId;
    }
}