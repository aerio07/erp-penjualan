<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseOrder;
use App\Traits\GeneratesDocumentNumber;
use InvalidArgumentException;

class PurchaseInvoiceService
{
    use GeneratesDocumentNumber;

    public function __construct(private JournalService $journalService) {}

    /**
     * Generate a PurchaseInvoice and associated Journal Entry from a GoodsReceipt.
     *
     * @param GoodsReceipt $grn
     * @param array $data ['supplier_invoice_number' => ..., 'invoice_date' => ..., 'due_date' => ..., 'tax_rate' => ..., 'notes' => ...]
     * @return PurchaseInvoice
     * @throws InvalidArgumentException
     */
    public function createFromGoodsReceipt(GoodsReceipt $grn, array $data = [], bool $asDraft = true): PurchaseInvoice
    {
        if ($grn->is_invoiced) {
            throw new InvalidArgumentException("LPB #{$grn->receipt_number} sudah pernah digunakan untuk invoice lain.");
        }

        $grn->loadMissing([
            'purchaseOrder.supplier',
            'purchaseOrder.items',
            'purchaseOrder.invoices.items',
            'items.purchaseOrderItem',
            'items.purchaseReturnItems.purchaseReturn',
        ]);

        $po = $grn->purchaseOrder;
        if (!$po) {
            throw new InvalidArgumentException("LPB tidak memiliki referensi Purchase Order yang valid.");
        }

        $taxRate = isset($data['tax_rate']) ? (float) $data['tax_rate'] : (float) ($po->tax_rate ?? 0);
        $invoiceDate = $data['invoice_date'] ?? ($grn->received_date ? $grn->received_date->toDateString() : now()->toDateString());
        
        // Pastikan periode akuntansi tidak ditutup
        app(\App\Services\AccountingPeriodService::class)->assertDateNotLocked($invoiceDate);

        $termDays = $po->supplier?->payment_term_days ?? 30;
        $dueDate = $data['due_date'] ?? \Carbon\Carbon::parse($invoiceDate)->addDays($termDays)->toDateString();
        $notes = $data['notes'] ?? ("Auto-generated dari LPB #" . $grn->receipt_number);
        $supplierInvoiceNumber = $data['supplier_invoice_number'] ?? null;

        $subtotalGrn = 0;
        $itemsToCreate = [];

        foreach ($grn->items as $grnItem) {
            $poItem = $grnItem->purchaseOrderItem;
            if (!$poItem) continue;

            $returnedCompleted = (int) $grnItem->purchaseReturnItems()
                ->where('source_type', 'accepted')
                ->whereHas('purchaseReturn', fn($q) => $q->where('status', 'completed'))
                ->sum('qty');
            $qty = max(0, (int) $grnItem->qty_received - $returnedCompleted);
            if ($qty <= 0) continue;

            $lineBase = $qty * $poItem->unit_price;
            $disc = $lineBase * ($poItem->discount_percent / 100);
            $netLine = $lineBase - $disc;
            $subtotalGrn += $netLine;

            $itemsToCreate[] = [
                'purchase_order_item_id' => $poItem->id,
                'goods_receipt_item_id'  => $grnItem->id,
                'product_id'             => $poItem->product_id,
                'qty_invoiced'           => $qty,
                'unit_price'             => $poItem->unit_price,
                'discount_percent'       => $poItem->discount_percent,
                'discount_amount'        => $disc,
                'subtotal'               => $netLine,
                'tax_amount'             => 0,
            ];
        }

        if (empty($itemsToCreate)) {
            throw new InvalidArgumentException("LPB #{$grn->receipt_number} tidak memiliki item yang dapat ditagih (atau seluruh item telah diretur).");
        }

        $usedHeaderDiscount = 0;
        $usedTaxAmount = 0;
        $totalInvoicedSubtotal = 0;
        foreach ($po->invoices as $existingInv) {
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

        $otherUninvoicedGrnsCount = GoodsReceipt::where('purchase_order_id', $po->id)
            ->where('is_invoiced', false)
            ->where('id', '!=', $grn->id)
            ->count();

        $totalOrderSubtotal = (float) $po->items->sum('subtotal');
        $headerDiscountAmount = (float) ($po->discount_amount ?? 0);

        $poFullyReceived = $po->items->every(function ($item) {
            return ($item->qty_received + $item->qty_rejected) >= $item->qty_ordered;
        });

        $isLastInvoiceForPo = ($otherUninvoicedGrnsCount === 0) && (
            $poFullyReceived ||
            in_array($po->status, ['done', 'completed']) ||
            round($subtotalGrn + $totalInvoicedSubtotal, 2) >= round($totalOrderSubtotal, 2)
        );

        $poTotalTax = (float) ($po->tax_amount ?? 0);
        $isIntegerRupiah = (round($poTotalTax) == $poTotalTax) && 
                           (round($totalOrderSubtotal) == $totalOrderSubtotal) && 
                           (round($headerDiscountAmount) == $headerDiscountAmount);
        $dec = $isIntegerRupiah ? 0 : 2;

        if ($isLastInvoiceForPo && $headerDiscountAmount > 0) {
            $proratedHeaderDiscount = max(0, round($headerDiscountAmount - $usedHeaderDiscount, $dec));
        } else {
            $proratedHeaderDiscount = $totalOrderSubtotal > 0
                ? round(($subtotalGrn / $totalOrderSubtotal) * $headerDiscountAmount, $dec)
                : 0;
        }

        $dpp = max(0, round($subtotalGrn - $proratedHeaderDiscount, $dec));

        if ($isLastInvoiceForPo && abs((float) $po->tax_rate - $taxRate) < 0.001 && $poTotalTax > 0) {
            $taxAmount = max(0, round($poTotalTax - $usedTaxAmount, $dec));
        } else {
            $taxAmount = round($dpp * ($taxRate / 100), $dec);
        }

        $totalAmount = round($dpp + $taxAmount, $dec);

        $invoice = PurchaseInvoice::create([
            'invoice_number'          => $this->generateNumber(),
            'purchase_order_id'       => $po->id,
            'goods_receipt_id'        => $grn->id,
            'supplier_invoice_number' => $supplierInvoiceNumber,
            'amount'                  => $dpp,
            'tax_rate'                => $taxRate,
            'tax_amount'              => $taxAmount,
            'total_amount'            => $totalAmount,
            'invoice_date'            => $invoiceDate,
            'due_date'                => $dueDate,
            'status'                  => $asDraft ? 'draft' : 'unpaid',
            'notes'                   => $notes,
        ]);

        $discountRatio = ($subtotalGrn > 0 && $proratedHeaderDiscount > 0)
            ? ($proratedHeaderDiscount / $subtotalGrn)
            : 0;

        $allocatedHeaderDiscount = 0;
        $allocatedSubtotal = 0;
        $allocatedTax = 0;
        $itemCount = count($itemsToCreate);

        foreach ($itemsToCreate as $idx => $itemData) {
            $itemData['purchase_invoice_id'] = $invoice->id;
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

            PurchaseInvoiceItem::create($itemData);
        }

        $grn->update([
            'is_invoiced'         => true,
            'purchase_invoice_id' => $invoice->id,
        ]);

        if (!$asDraft) {
            $entry = $this->journalService->createFromPurchaseInvoice($invoice);
            $this->journalService->postEntry($entry);
        }

        return $invoice;
    }

    /**
     * Post a draft PurchaseInvoice and generate its journal entry.
     *
     * @param PurchaseInvoice $invoice
     * @return PurchaseInvoice
     * @throws InvalidArgumentException
     */
    public function postInvoice(PurchaseInvoice $invoice): PurchaseInvoice
    {
        if ($invoice->status !== 'draft') {
            throw new InvalidArgumentException("Hanya invoice berstatus Draft yang dapat diposting.");
        }

        $invoice->update(['status' => 'unpaid']);

        $entry = $this->journalService->createFromPurchaseInvoice($invoice);
        $this->journalService->postEntry($entry);

        return $invoice;
    }

    public function generateNumber(): string
    {
        return $this->generateDocumentNumber(PurchaseInvoice::class, 'invoice_number', 'PINV');
    }
}
