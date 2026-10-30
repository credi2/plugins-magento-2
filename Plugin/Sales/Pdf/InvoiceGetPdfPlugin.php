<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Plugin\Sales\Pdf;

use Credi2\CashpressoPayments\Model\Pdf\InvoiceRenderingState;
use Magento\Sales\Model\Order\Pdf\Invoice;

class InvoiceGetPdfPlugin
{
    public function __construct(
        private readonly InvoiceRenderingState $invoiceRenderingState
    ) {
    }

    public function aroundGetPdf(Invoice $subject, callable $proceed, $invoices = [])
    {
        $this->invoiceRenderingState->setRenderingInvoice(true);
        try {
            return $proceed($invoices);
        } finally {
            $this->invoiceRenderingState->setRenderingInvoice(false);
        }
    }
}
