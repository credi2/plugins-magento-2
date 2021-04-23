<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace LimeSoda\Cashpresso\Model\Ui;

use LimeSoda\Cashpresso\Gateway\Config;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'cashpresso';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var SessionManagerInterface
     */
    protected $session;

    protected $checkoutSession;

    /**
     * Constructor
     *
     * @param Config $config
     * @param SessionManagerInterface $session
     */
    public function __construct(
        Config $config,
        CheckoutSession $checkoutSession,
        SessionManagerInterface $session
    ) {
        $this->config = $config;
        $this->session = $session;
        $this->checkoutSession = $checkoutSession;
    }

    private function isAllowed()
    {
        $items = $this->checkoutSession->getQuote()->getItems();

        $status = true;

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($items as $item) {
            $status = in_array($item->getProduct()->getTypeId(), ['virtual', 'downloadable', 'giftcard']) ? false : true;

            if (!$status){
                break;
            }
        }

        return $status;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $storeId = $this->session->getStoreId();

        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $this->config->isActive($storeId) && $this->isAllowed(),
                    'credit_limit' => $this->config->getTotalLimit(),
                    'min_limit' => $this->config->getMinPaybackAmount(),
                    'api_key' => $this->config->getAPIKey($storeId),
                    'debug' => $this->config->isDebugEnabled()
                ]
            ]
        ];
    }
}
