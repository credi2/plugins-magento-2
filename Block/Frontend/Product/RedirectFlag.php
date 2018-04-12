<?php


namespace LimeSoda\Cashpresso\Block\Frontend\Product;

use Magento\Framework\View\Element\Template;
use LimeSoda\Cashpresso\Gateway\Config;

class RedirectFlag extends Template
{
    private $config;

    /**
     * RedirectFlag constructor.
     * @param Template\Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $config,
        array $data = [])
    {
        parent::__construct($context, $data);

        $this->config = $config;
    }

    protected function _toHtml()
    {
        if ($this->config->showCheckoutButton()) {
            return <<<EOT
<input type="hidden" name="cs_redirect_to_checkout" value="1" />
EOT;

        }

        return '';
    }
}