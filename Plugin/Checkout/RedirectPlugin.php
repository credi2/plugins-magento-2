<?php
/**
 * 31.05.18
 * LimeSoda - Limesoda M2Demo
 *
 * Created by Anton Sannikov.
 *
 * @category    Lime_Soda
 * @package     Limesoda M2Demo
 * @copyright   Copyright (c) 2018 LimeSoda. (http://www.limesoda.com)
 *
 * @file RedirectPlugin.php
 */

namespace LimeSoda\Cashpresso\Plugin\Checkout;

class RedirectPlugin
{
    protected $csConfig;

    protected $checkoutSession;

    protected $orderConfig;

    protected $httpContext;

    protected $store;

    protected $registry;

    protected $context;

    public function __construct(\LimeSoda\Cashpresso\Gateway\Config $config,
                                \Magento\Checkout\Model\Session $checkoutSession,
                                \Magento\Sales\Model\Order\Config $orderConfig,
                                \Magento\Framework\App\Http\Context $httpContext,
                                \LimeSoda\Cashpresso\Helper\Store $store,
                                \Magento\Framework\Registry $registry,
                                \Magento\Framework\App\Action\Context $context
    )
    {
        $this->csConfig = $config;
        $this->checkoutSession = $checkoutSession;
        $this->orderConfig = $orderConfig;
        $this->httpContext = $httpContext;
        $this->store = $store;
        $this->registry = $registry;
        $this->context = $context;
    }

    /**
     * @param \Magento\Checkout\Controller\Cart $cart
     * @param $currentTemplate
     */
    public function afterExecute(
        \Magento\Checkout\Controller\Cart\Add $cart,
        $value
    )
    {
        if ($this->registry->registry('cs_redirect_to_checkout')) {
            if ($value instanceof \Magento\Framework\Controller\Result\Redirect) {
                $value->setUrl($this->context->getRequest()->getParam('cs_return_url'));
            } else {
                $result = [
                    'backUrl' => $this->context->getRequest()->getParam('cs_return_url')
                ];

                return $this->context->getResponse()->representJson(
                    $this->context->getObjectManager()->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($result)
                );
            }
        }

        return $value;
    }
}