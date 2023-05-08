<?php

namespace LimeSoda\Cashpresso\Test\Unit\Model;

use Magento\Store\Model\ScopeInterface;

class CustomerSessionTest extends \PHPUnit\Framework\TestCase
{
    /** @var Customer */
    protected $model;

    /** @var \LimeSoda\Cashpresso\Model\CustomerSession */
    protected $cashpressoCustomer;

    protected $customerSessionMock;

    protected $checkoutSessionMock;

    protected $customerMock;

    protected $shippingAddress;

    protected $billingAddress;

    protected function setUp(): void
    {
        $this->customerSessionMock = $this->createMock(\Magento\Customer\Model\Session::class);
        $this->checkoutSessionMock = $this->createMock(\Magento\Checkout\Model\Session::class);

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->cashpressoCustomer = $objectManager->getObject('\LimeSoda\Cashpresso\Model\CustomerSession', [
            'customerSession' => $this->customerSessionMock,
            'checkoutSession' => $this->checkoutSessionMock
        ]);

        /** @var \Magento\Quote\Model\Quote\Address billingAddress */
        $this->billingAddress = $this->createPartialMock(\Magento\Customer\Model\Customer::class,
            ['getId', 'getFirstname', 'getEmail', 'getLastname', 'getTaxvat', 'getDob']
        );

        $this->customerMock->method('getId')->willReturn(1);
        $this->customerMock->method('getFirstname')->willReturn('John');
        $this->customerMock->method('getLastname')->willReturn('Smith');
        $this->customerMock->method('getDob')->willReturn('1983-07-04');
        $this->customerMock->method('getTaxvat')->willReturn('0123456789');
        $this->customerMock->method('getEmail')->willReturn('test@example.com');

        $this->customerSessionMock->method('getCustomer')->willReturn($this->customerMock);

    }

    public function testGetCustomerAccountData()
    {
        $data = $this->cashpressoCustomer->getCustomerAccountData();

        $this->assertEquals([
            'email' => 'test@example.com',
            'firstname' => 'John',
            'lastname' => 'Smith',
            'dob' => '1983-07-04',
            'taxvat' => '0123456789',
        ], $data);
    }

    public function testPrepareCustomerAccountData()
    {
        $inputData = new \Magento\Framework\DataObject();

        $outputData = $this->cashpressoCustomer->prepareCustomerAccountData(clone $inputData);

        $this->assertNotEquals($inputData->getData(), $outputData->getData());

        $inputData->setEmail('test1@example.com');

        $outputData = $this->cashpressoCustomer->prepareCustomerAccountData($inputData);

        $this->assertEquals('test@example.com', $outputData->getEmail());

        $inputData->setCustomField('some_value');
        $outputData = $this->cashpressoCustomer->prepareCustomerAccountData(clone $inputData);

        $this->assertEquals('some_value', $outputData->getCustomField());

        $inputData->unsetData(['custom_field', 'email']);

        $outputData = $this->cashpressoCustomer->prepareCustomerAccountData(clone $inputData);

        $this->assertEquals($inputData->getData(), $outputData->getEmail());
    }
}
