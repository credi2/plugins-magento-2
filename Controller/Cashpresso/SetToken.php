<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Controller\Cashpresso;

use Exception;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class SetToken implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory      $jsonFactory,
        private readonly CheckoutSession  $checkoutSession,
        private readonly LoggerInterface  $logger
    ) {
    }

    public function execute(): ResultInterface
    {
        $result = $this->jsonFactory->create();

        try {
            $quote = $this->checkoutSession->getQuote();
            if (!$quote->getId()) {
                throw new LocalizedException(__('No active quote found'));
            }

            $payment = $quote->getPayment();
            $payment->setAdditionalInformation('cashpresso_token', $this->request->getParam('cashpresso_token'));
            $quote->save();

            $result->setData([
                'success' => true,
            ]);

        } catch (Exception $e) {
            $this->logger->error('SetToken error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            $result->setData([
                'success' => false,
                'message' => __('Failed to SetToken: %1', $e->getMessage()),
            ]);
        }

        return $result;
    }
}
