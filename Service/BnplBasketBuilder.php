<?php
declare(strict_types=1);

namespace Credi2\CashpressoPayments\Service;

use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

class BnplBasketBuilder
{
    public function buildFromQuote(Quote $quote): array
    {
        $lineItems = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            $lineItems[] = $this->buildLineItem(
                (string)$item->getSku(),
                (string)$item->getName(),
                (string)($item->getDescription() ?: $item->getName()),
                (int)$item->getQty(),
                (float)$item->getPriceInclTax(),
                (float)$item->getTaxAmount()
            );
        }

        $shippingAddress = $quote->getShippingAddress();
        if ($shippingAddress && $shippingAddress->getShippingAmount() > 0) {
            $lineItems[] = $this->buildLineItem(
                'shipping',
                'Shipping',
                (string)$shippingAddress->getShippingDescription(),
                1,
                (float)$shippingAddress->getShippingInclTax(),
                (float)$shippingAddress->getShippingTaxAmount()
            );
        }

        return $this->finalizeBasket(
            $lineItems,
            (float)$quote->getGrandTotal(),
            (float)$quote->getTaxAmount(),
            (string)$quote->getQuoteCurrencyCode()
        );
    }

    public function buildFromOrder(Order $order): array
    {
        $lineItems = [];

        foreach ($order->getAllVisibleItems() as $item) {
            $lineItems[] = $this->buildLineItem(
                (string)$item->getSku(),
                (string)$item->getName(),
                (string)($item->getDescription() ?: $item->getName()),
                (int)$item->getQtyOrdered(),
                (float)$item->getPriceInclTax(),
                (float)$item->getTaxAmount()
            );
        }

        if ($order->getShippingAmount() > 0) {
            $lineItems[] = $this->buildLineItem(
                'shipping',
                'Shipping',
                (string)$order->getShippingDescription(),
                1,
                (float)$order->getShippingInclTax(),
                (float)$order->getShippingTaxAmount()
            );
        }

        return $this->finalizeBasket(
            $lineItems,
            (float)$order->getGrandTotal(),
            (float)$order->getTaxAmount(),
            (string)$order->getOrderCurrencyCode()
        );
    }

    private function buildLineItem(
        string $productId,
        string $name,
        string $description,
        int $qty,
        float $unitGrossPrice,
        float $rowTaxAmount
    ): array {
        $qty = max(1, $qty);

        return [
            'productId' => $productId,
            'productName' => $name,
            'productDescription' => $description,
            'productQuantity' => $qty,
            'productUnitGrossPrice' => (int)round($unitGrossPrice * 100),
            'productUnitTaxAmount' => (int)round($rowTaxAmount * 100 / $qty),
        ];
    }

    private function finalizeBasket(array $lineItems, float $grandTotal, float $taxAmount, string $currency): array
    {
        $grossAmount = (int)round($grandTotal * 100);

        $lineItemSum = 0;
        foreach ($lineItems as $lineItem) {
            $lineItemSum += $lineItem['productUnitGrossPrice'] * $lineItem['productQuantity'];
        }

        // Discounts (coupons, cart price rules) and rounding residues are not part of
        // the product lines; balance them so the line items always sum to grossAmount.
        $difference = $grossAmount - $lineItemSum;
        if ($difference !== 0) {
            $label = $difference < 0 ? 'Discount' : 'Adjustment';
            $lineItems[] = [
                'productId' => strtolower($label),
                'productName' => $label,
                'productDescription' => $label,
                'productQuantity' => 1,
                'productUnitGrossPrice' => $difference,
                'productUnitTaxAmount' => 0,
            ];
        }

        return [
            'grossAmount' => $grossAmount,
            'taxAmount' => (int)round($taxAmount * 100),
            'currency' => $currency,
            'lineItems' => $lineItems,
        ];
    }
}
