<?php

namespace App\Services;

use App\Enums\DeliveryStatus;
use App\Enums\SalesInvoiceStatus;
use App\Enums\SalesOrderStatus;
use App\Models\Delivery;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Traits\GeneratesDocumentNumber;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SalesInvoiceService
{
    use GeneratesDocumentNumber;

    public function __construct(private JournalService $journalService) {}

    /**
     * Generate a SalesInvoice and associated Journal Entry from a delivered Delivery (Surat Jalan).
     *
     * @param Delivery $delivery
     * @param array $data ['invoice_date' => ..., 'due_date' => ..., 'tax_rate' => ..., 'notes' => ...]
     * @return SalesInvoice
     * @throws InvalidArgumentException
     */
    public function createFromDelivery(Delivery $delivery, array $data = [], bool $asDraft = true): SalesInvoice
    {
        if ($delivery->is_invoiced) {
            throw new InvalidArgumentException("Surat Jalan #{$delivery->delivery_number} sudah pernah digunakan untuk invoice lain.");
        }

        if ($delivery->status !== DeliveryStatus::Delivered) {
            throw new InvalidArgumentException("Surat Jalan #{$delivery->delivery_number} belum berstatus diterima (Delivered). Invoice tidak dapat diterbitkan sebelum barang sampai/diterima.");
        }

        $delivery->loadMissing([
            'salesOrder.customer',
            'salesOrder.items',
            'salesOrder.invoices.items',
            'items.salesOrderItem.product',
            'items.salesReturnItems.salesReturn',
        ]);

        $so = $delivery->salesOrder;
        if (!$so) {
            throw new InvalidArgumentException("Surat Jalan tidak memiliki referensi Sales Order yang valid.");
        }

        $taxRate = isset($data['tax_rate']) ? (float) $data['tax_rate'] : (float) ($so->tax_rate ?? 0);
        $invoiceDate = $data['invoice_date'] ?? now()->toDateString();

        // Pastikan periode akuntansi tidak ditutup
        app(\App\Services\AccountingPeriodService::class)->assertDateNotLocked($invoiceDate);

        $termDays = $so->customer?->payment_term_days ?? 30;
        $dueDate = $data['due_date'] ?? \Carbon\Carbon::parse($invoiceDate)->addDays($termDays)->toDateString();
        $notes = !empty(trim($data['notes'] ?? '')) ? trim($data['notes']) : null;

        $subtotalDelivery = 0;
        $cogs = 0;
        $itemsToCreate = [];

        foreach ($delivery->items as $delItem) {
            $soItem = $delItem->salesOrderItem;
            if (!$soItem) continue;

            $returnedCompleted = (int) $delItem->salesReturnItems()
                ->whereHas('salesReturn', fn($q) => $q->whereIn('status', ['received', 'completed']))
                ->sum('qty');
            $qty = max(0, (int) $delItem->qty_delivered - $returnedCompleted);
            if ($qty <= 0) continue;

            $lineBase = $qty * $soItem->unit_price;
            $disc = $lineBase * ($soItem->discount_percent / 100);
            $netLine = $lineBase - $disc;
            $subtotalDelivery += $netLine;

            $itemCogs = $qty * ($soItem->product->purchase_price ?? 0);
            $cogs += $itemCogs;

            $itemsToCreate[] = [
                'sales_order_item_id' => $soItem->id,
                'delivery_item_id'    => $delItem->id,
                'product_id'          => $soItem->product_id,
                'qty_invoiced'        => $qty,
                'unit_price'          => $soItem->unit_price,
                'discount_percent'    => $soItem->discount_percent,
                'discount_amount'     => $disc,
                'subtotal'            => $netLine,
                'tax_amount'          => 0,
                'cogs_amount'         => $itemCogs,
            ];
        }

        if (empty($itemsToCreate)) {
            throw new InvalidArgumentException("Surat Jalan #{$delivery->delivery_number} tidak memiliki item yang dapat ditagih.");
        }

        // Hitung akumulasi diskon header & PPN dari invoice terdahulu
        $usedHeaderDiscount = 0;
        $usedTaxAmount = 0;
        $totalInvoicedSubtotal = 0;
        foreach ($so->invoices as $existingInv) {
            $invBaseSubtotal = 0;
            foreach ($existingInv->items as $invItem) {
                $lineBase = $invItem->qty_invoiced * $invItem->unit_price;
                $lineDisc = $lineBase * (($invItem->discount_percent ?? 0) / 100);
                $invBaseSubtotal += ($lineBase - $lineDisc);
            }
            $totalInvoicedSubtotal += $invBaseSubtotal;
            $usedHeaderDiscount += max(0, round($invBaseSubtotal - $existingInv->amount, 2));
            $usedTaxAmount += (float) $existingInv->tax_amount;
        }

        $otherUninvoicedDeliveriesCount = Delivery::where('sales_order_id', $so->id)
            ->where('is_invoiced', false)
            ->where('id', '!=', $delivery->id)
            ->count();

        $totalOrderSubtotal = (float) $so->items->sum('subtotal');
        $headerDiscountAmount = (float) ($so->discount_amount ?? 0);

        $soFullyDelivered = $so->items->every(function ($item) {
            return $item->qty_delivered >= $item->qty_ordered;
        });

        $isLastInvoiceForSo = ($otherUninvoicedDeliveriesCount === 0) && (
            $soFullyDelivered ||
            $so->status === SalesOrderStatus::Done ||
            round($subtotalDelivery + $totalInvoicedSubtotal, 2) >= round($totalOrderSubtotal, 2)
        );

        $soTotalTax = (float) ($so->tax_amount ?? 0);
        $isIntegerRupiah = (round($soTotalTax) == $soTotalTax) &&
                           (round($totalOrderSubtotal) == $totalOrderSubtotal) &&
                           (round($headerDiscountAmount) == $headerDiscountAmount);
        $dec = $isIntegerRupiah ? 0 : 2;

        if ($isLastInvoiceForSo && $headerDiscountAmount > 0) {
            $proratedHeaderDiscount = max(0, round($headerDiscountAmount - $usedHeaderDiscount, $dec));
        } else {
            $proratedHeaderDiscount = $totalOrderSubtotal > 0
                ? round(($subtotalDelivery / $totalOrderSubtotal) * $headerDiscountAmount, $dec)
                : 0;
        }

        $dpp = max(0, round($subtotalDelivery - $proratedHeaderDiscount, $dec));

        if ($isLastInvoiceForSo && abs((float) $so->tax_rate - $taxRate) < 0.001 && $soTotalTax > 0) {
            $taxAmount = max(0, round($soTotalTax - $usedTaxAmount, $dec));
        } else {
            $taxAmount = round($dpp * ($taxRate / 100), $dec);
        }

        $totalAmount = round($dpp + $taxAmount, $dec);

        $invoice = SalesInvoice::create([
            'invoice_number' => $this->generateNumber(),
            'sales_order_id' => $so->id,
            'delivery_id'    => $delivery->id,
            'amount'         => $dpp,
            'tax_rate'       => $taxRate,
            'tax_amount'     => $taxAmount,
            'total_amount'   => $totalAmount,
            'invoice_date'   => $invoiceDate,
            'due_date'       => $dueDate,
            'status'         => $asDraft ? 'draft' : 'unpaid',
            'notes'          => $notes,
        ]);

        $discountRatio = ($subtotalDelivery > 0 && $proratedHeaderDiscount > 0)
            ? ($proratedHeaderDiscount / $subtotalDelivery)
            : 0;

        $allocatedHeaderDiscount = 0;
        $allocatedSubtotal = 0;
        $allocatedTax = 0;
        $itemCount = count($itemsToCreate);

        foreach ($itemsToCreate as $idx => $itemData) {
            $itemData['sales_invoice_id'] = $invoice->id;
            $isLastItem = ($idx === $itemCount - 1);

            if ($isLastItem) {
                $itemHeaderDiscount = max(0, round($proratedHeaderDiscount - $allocatedHeaderDiscount, $dec));
                $itemNetSubtotal    = max(0, round($dpp - $allocatedSubtotal, $dec));
                $itemTax            = max(0, round($taxAmount - $allocatedTax, $dec));
            } else {
                $itemHeaderDiscount = round($itemData['subtotal'] * $discountRatio, $dec);
                $itemNetSubtotal    = max(0, round($itemData['subtotal'] - $itemHeaderDiscount, $dec));
                $itemTax            = round($itemNetSubtotal * ($taxRate / 100), $dec);

                $allocatedHeaderDiscount += $itemHeaderDiscount;
                $allocatedSubtotal       += $itemNetSubtotal;
                $allocatedTax            += $itemTax;
            }

            $itemData['discount_amount'] = round($itemData['discount_amount'] + $itemHeaderDiscount, $dec);
            $itemData['subtotal']        = $itemNetSubtotal;
            $itemData['tax_amount']      = $itemTax;

            SalesInvoiceItem::create($itemData);
        }

        $delivery->update([
            'is_invoiced'      => true,
            'sales_invoice_id' => $invoice->id,
        ]);

        foreach ($delivery->items as $delItem) {
            $delItem->update(['invoiced_qty' => $delItem->qty_delivered]);
        }

        if (!$asDraft) {
            $entry = $this->journalService->createFromSalesInvoice($invoice, $cogs);
            $this->journalService->postEntry($entry);
        }

        return $invoice;
    }

    /**
     * Post a draft SalesInvoice and generate its journal entry.
     *
     * @param SalesInvoice $invoice
     * @return SalesInvoice
     * @throws InvalidArgumentException
     */
    public function postInvoice(SalesInvoice $invoice): SalesInvoice
    {
        if (!$invoice->isDraft()) {
            throw new InvalidArgumentException("Hanya invoice berstatus Draft yang dapat diposting.");
        }

        app(\App\Services\AccountingPeriodService::class)->assertDateNotLocked($invoice->invoice_date);

        $invoice->loadMissing(['items.product', 'salesOrder.customer']);

        $cogs = (float) $invoice->items->sum('cogs_amount');

        $invoice->update(['status' => SalesInvoiceStatus::Unpaid]);

        $entry = $this->journalService->createFromSalesInvoice($invoice, $cogs);
        $this->journalService->postEntry($entry);

        return $invoice;
    }

    public function generateNumber(): string
    {
        return $this->generateDocumentNumber(SalesInvoice::class, 'invoice_number', 'INV');
    }
}
