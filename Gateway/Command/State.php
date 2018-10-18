<?php

namespace LimeSoda\Cashpresso\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;

class State implements CommandInterface
{
    /**
     * @see \Magento\Payment\Model\Method\Adapter::initialize()
     * @param array $commandSubject
     * @return $this|\Magento\Payment\Gateway\Command\ResultInterface|null
     */
    public function execute(array $commandSubject)
    {
        return $this;
    }
}