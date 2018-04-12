<?php



namespace LimeSoda\Cashpresso\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\ConfigInterface;

class CurrencyValidator extends AbstractValidator
{
    /**
     * @var \LimeSoda\Cashpresso\Gateway\Config
     */
    private $config;

    protected $storeManager;

    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ConfigInterface $config
    )
    {
        $this->config = $config;

        parent::__construct($resultFactory);

        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function validate(array $validationSubject)
    {
        $code = $this->storeManager->getStore($validationSubject['storeId'])->getCurrentCurrencyCode();
        return $this->createResult(strtoupper($code) == strtoupper($this->config->getContractCurrency()));
    }
}