<?php


namespace LimeSoda\Cashpresso\Test\Unit;

class ClassConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
    }

    protected function tearDown()
    {
        $this->objectManager  = null;
    }

    public function testFakeTest()
    {
        $this->assertTrue(true);
    }
}
