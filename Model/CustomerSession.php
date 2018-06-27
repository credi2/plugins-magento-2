<?php


namespace LimeSoda\Cashpresso\Model;

class CustomerSession
{
    private $customerSession;

    private $checkoutSession;

    /**
     * @var Quote|null
     */
    protected $quote = null;

    protected $customer = null;

    protected $customerRepository;

    protected $addressRepository;

    public function __construct(\Magento\Customer\Model\Session $customerSession,
                                \Magento\Checkout\Model\Session $checkoutSession,
                                \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
                                \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
    )
    {
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
    }

    /**
     * Get active quote
     *
     * @return Quote
     */
    public function getQuote()
    {
        if (null === $this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }
        return $this->quote;
    }

    public function getCustomer()
    {
        return $this->customerSession->getCustomer();
    }

    /**
     * @return array
     */
    public function getCustomerAccountData()
    {
        $data = [];

        if ($this->getCustomer()->getId()) {

            $customer = $this->getCustomer();

            $data = [
                'email' => $customer->getEmail(),
                'firstname' => $customer->getFirstname(),
                'lastname' => $customer->getLastname(),
                'dob' => $customer->getDob(),
                'taxvat' => $customer->getTaxvat()
            ];
        }

        return $data;
    }

    /**
     * @return \Magento\Framework\DataObject
     */
    public function getQuoteCustomerData()
    {
        /** @var \Magento\Quote\Model\Quote\Address $quoteShippingAddress */
        $quoteShippingAddress = $this->getQuote()->getShippingAddress();
        $quoteShippingAddress->setIsActive(true);

        /** @var \Magento\Quote\Model\Quote\Address $quoteBillingAddress */
        $quoteBillingAddress = $this->getQuote()->getBillingAddress();
        $quoteBillingAddress->setIsActive(true);

        return $this->getQuoteAddresses($quoteBillingAddress, $quoteShippingAddress);
    }

    /**
     * @param $data \Magento\Framework\DataObject
     * @return \Magento\Framework\DataObject
     */
    public function prepareCustomerAccountData($data)
    {
        $customerData = new \Magento\Framework\DataObject();
        $customerData->setData($this->getCustomerAccountData());

        $data->setData(array_replace_recursive($data->getData(), $customerData->getData()));

        return $data;
    }

    /**
     * @param $data \Magento\Framework\DataObject
     * @return \Magento\Framework\DataObject
     */
    public function prepareQuoteCustomerData($data)
    {
        $customerData = $this->getQuoteCustomerData();

        $data->setData(array_replace_recursive($data->getData(), $customerData->getData()));

        return $data;
    }

    /**
     * @param \Magento\Customer\Model\Address $billingAddress
     * @param \Magento\Customer\Model\Address $shippingAddress
     * @return \Magento\Framework\DataObject
     */
    public function getQuoteAddresses($billingAddress, $shippingAddress)
    {
        $addressData = new \Magento\Framework\DataObject();

        foreach ([$billingAddress, $shippingAddress] as $address) {
            if ($address && $address->getIsActive()) {
                $dataObject = new \Magento\Framework\DataObject();
                $dataObject->setData([
                    'email' => $address->getEmail(),
                    'firstname' => $address->getFirstname(),
                    'lastname' => $address->getLastname(),
                    'postcode' => $address->getPostcode(),
                    'street' => str_replace("\n", ", ", $address->getStreetFull()),
                    'city' => $address->getCity(),
                    'country_id' => $address->getCountryId(),
                    'taxvat' => $address->getVatId(),
                    'telephone' => $address->getTelephone(),
                ]);

                foreach ($dataObject->getData() as $field => $value) {
                    if (empty($value)){
                        $dataObject->unsetData($field);
                    }
                }

                $addressData->setData(array_replace_recursive($addressData->getData(), $dataObject->getData()));
            }
        }

        return $addressData;
    }

    /**
     * @return \Magento\Framework\DataObject
     */
    public function getDefaultAddresses()
    {
        $addressData = new \Magento\Framework\DataObject();

        if ($this->getCustomer()->getId()) {
            /** @var \Magento\Customer\Model\Address $billingAddress */
            $billingAddress = $this->getCustomer()->getDefaultBillingAddress();

            /** @var \Magento\Customer\Model\Address $shippingAddress */
            $shippingAddress = $this->getCustomer()->getDefaultShippingAddress();

            $addressData = $this->getQuoteAddresses($billingAddress, $shippingAddress);
        }

        return $addressData;
    }

    /**
     * @param $data \Magento\Framework\DataObject
     * @return \Magento\Framework\DataObject
     */
    public function prepareDefaultAddresses($data)
    {
        $addressData = $this->getDefaultAddresses();

        $data->setData(array_replace_recursive($data->getData(), $addressData->getData()));

        return $data;
    }

    public function getCustomerData()
    {
        if (null === $this->customer) {
            $data = new \Magento\Framework\DataObject();

            $customerData = $this->prepareCustomerAccountData($data);
            $addressData = $this->prepareDefaultAddresses($customerData);
            $this->customer = $this->prepareQuoteCustomerData($addressData);

        }

        return $this->customer;
    }
}
