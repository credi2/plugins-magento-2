<?php


namespace LimeSoda\Cashpresso\Test\Unit;

class ClassConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
    }

    protected function tearDown(): void
    {
        $this->objectManager  = null;
    }

    public function testFakeTest()
    {
        $this->assertTrue(true);
    }
}
