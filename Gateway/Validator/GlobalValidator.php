<?php


namespace LimeSoda\Cashpresso\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Request\Http;

class GlobalValidator extends AbstractValidator
{
    /**
     * @var \LimeSoda\Cashpresso\Gateway\Config
     */
    protected ConfigInterface $config;

    protected Http $http;

    protected Session $session;

    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory,
        Http $http,
        Session $session,
        ConfigInterface $config
    ) {
        $this->config = $config;
        $this->http = $http;
        $this->session = $session;
        parent::__construct($resultFactory);
    }

    private function isAllowed()
    {
        $items = $this->session->getQuote()->getItems();
        $status = true;
        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($items as $item)
        {
            if (in_array($item->getProduct()->getTypeId(), ['virtual', 'downloadable', 'giftcard'], true))
            {
                $status = false;
                break;
            }
        }

        return $status;
    }

    /**
     * @inheritdoc
     */
    public function validate(array $validationSubject)
    {
        return $this->createResult($this->isAllowed());
    }
}
