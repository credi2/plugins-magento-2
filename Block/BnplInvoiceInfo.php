<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Block;

use Credi2\CashpressoPayments\Model\Pdf\InvoiceRenderingState;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order\Payment;

class BnplInvoiceInfo extends Info
{
    public const PAYMENT_DATA = [
        'holder' => 'Raiffeisen Bank International AG',
        'iban' => 'AT963100000204973764',
        'bic' => 'RZBAATWW',
    ];

    public function __construct(
        Context $context,
        private readonly InvoiceRenderingState $invoiceRenderingState,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function toPdf()
    {
        if ($this->invoiceRenderingState->isRenderingInvoice()) {
            $this->setTemplate('Credi2_CashpressoPayments::info/pdf/bnpl_invoice.phtml');
            return $this->toHtml();
        }

        return parent::toPdf();
    }

    public function getBankData(): array
    {
        return self::PAYMENT_DATA;
    }

    public function getPaymentReference(): string
    {
        $payment = $this->getInfo();
        if($bnplOrderData = $payment->getAdditionalInformation('cashpresso_bnpl_order_data')) {
            // handle legacy cases
            if(is_string($bnplOrderData)) {
                $bnplOrderData = json_decode($bnplOrderData, true);
            }elseif (is_object($bnplOrderData)) {
                $bnplOrderData = json_decode(json_encode($bnplOrderData), true);
            }
            if(isset($bnplOrderData['paymentReference'])) {
                return $bnplOrderData['paymentReference'];
            }
        }

        //fallback
        if ($payment instanceof Payment && $payment->getOrder()) {
            return (string)$payment->getOrder()->getIncrementId();
        }

        return '';
    }
}
