<?php


namespace LimeSoda\Cashpresso\Plugin\Checkout;

#use Magento\Framework\App\Action\Context;

class OnepageSuccessPlugin
{
    private $csConfig;

    private $checkoutSession;

    private $orderConfig;

    private $httpContext;

    private $store;

    public function __construct(\LimeSoda\Cashpresso\Gateway\Config $config,
                                \Magento\Checkout\Model\Session $checkoutSession,
                                \Magento\Sales\Model\Order\Config $orderConfig,
                                \Magento\Framework\App\Http\Context $httpContext,
                                \LimeSoda\Cashpresso\Helper\Store $store
    )
    {
        $this->csConfig = $config;
        $this->checkoutSession = $checkoutSession;
        $this->orderConfig = $orderConfig;
        $this->httpContext = $httpContext;
        $this->store = $store;
    }

    /**
     * @param \Magento\Checkout\Block\Onepage\Success $block
     * @param $currentTemplate
     */
    public function afterGetTemplate(
        \Magento\Framework\View\Element\BlockInterface $block,
        $currentTemplate
    )
    {
        $order = $this->checkoutSession->getLastRealOrder();

        $purchaseId = $order->getId() && $order->getPayment() ? $order->getPayment()->getData(\Magento\Sales\Api\Data\OrderPaymentInterface::ADDITIONAL_INFORMATION . '/purchaseId') : null;

        if ($block instanceof \Magento\Multishipping\Block\Checkout\Success) {
            return "LimeSoda_Cashpresso::checkout/multishipping/success.phtml";
        }

        if ($purchaseId && ($block->getNameInLayout() == 'checkout.success.print.button')) {
            $block->setData('can_print_order', false);
            return $currentTemplate;
        }

        if ($purchaseId && ($block->getNameInLayout() == 'checkout.success')) {
            $block->setData('purchase_id', $purchaseId);
            $block->setData('cashpresso_config', $this->csConfig);
            $block->setData('locale', $this->store->getLocale());

            return "LimeSoda_Cashpresso::checkout/success.phtml";
        }

        return $currentTemplate;
    }
}