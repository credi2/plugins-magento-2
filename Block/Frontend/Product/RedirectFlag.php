<?php

namespace LimeSoda\Cashpresso\Block\Frontend\Product;

use Magento\Framework\View\Element\Template;
use LimeSoda\Cashpresso\Gateway\Config;

class RedirectFlag extends Template
{
    protected Config $config;

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
        $urlCheckout = $this->getUrl($this->config->getCheckoutUrl());

        if ($this->config->showCheckoutButton()) {
            return <<<EOT
<input type="hidden" name="cs_redirect_to_checkout" value="0" />
<input type="hidden" name="cs_return_url" value="{$urlCheckout}" />
EOT;
        }

        return '';
    }
}
