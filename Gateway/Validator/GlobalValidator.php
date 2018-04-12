<?php



namespace LimeSoda\Cashpresso\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Checkout\Model\Session;

class GlobalValidator extends AbstractValidator
{
    /**
     * @var \LimeSoda\Cashpresso\Gateway\Config
     */
    private $config;

    private $http;

    private $session;

    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory,
        \Magento\Framework\App\Request\Http $http,
        Session $session,
        ConfigInterface $config
    )
    {
        $this->config = $config;

        $this->http = $http;

        $this->session = $session;

        parent::__construct($resultFactory);
    }

    private function isAllowed()
    {
        $items = $this->session->getQuote()->getItems();

        $status = true;

        /** @var \‌Magento\Quote\Model\Quote\Item $item */
        foreach ($items as $item) {
            $status = in_array($item->getProduct()->getTypeId(), ['virtual', 'downloadable', 'giftcard']) ? false : true;

            if (!$status){
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