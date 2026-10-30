<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Model\Pdf;

/**
 * Shared flag indicating that an invoice PDF is currently being rendered.
 * Payment info blocks are reused for invoice, shipment and creditmemo PDFs and
 * cannot tell the document types apart on their own.
 */
class InvoiceRenderingState
{
    private bool $renderingInvoice = false;

    public function setRenderingInvoice(bool $renderingInvoice): void
    {
        $this->renderingInvoice = $renderingInvoice;
    }

    public function isRenderingInvoice(): bool
    {
        return $this->renderingInvoice;
    }
}
