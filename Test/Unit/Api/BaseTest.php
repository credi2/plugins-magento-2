<?php


namespace LimeSoda\Cashpresso\Test\Unit\Api;

use LimeSoda\Cashpresso\Gateway\Checkout;

class BaseTest extends \PHPUnit\Framework\TestCase
{
    /** @var Checkout */
    protected $checkoutRequest;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
    }

    public function testGetPartnerInfo()
    {
        $this->getMockBuilder();

        $this->objectManager->getObject(
            Checkout::class
        );
    }
}