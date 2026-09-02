<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    protected $appends = [
        'qty_returned_accepted',
        'qty_returned_rejected',
        'qty_returned_completed_accepted',
        'qty_available_for_return_accepted',
        'qty_available_for_return_rejected',
        'qty_available_for_invoice',
    ];

    protected $fillable = [
        'goods_receipt_id', 'purchase_order_item_id', 'warehouse_id',
        'qty_received', 'qty_rejected', 'invoiced_qty', 'unit_cost', 'condition', 'shortage_reason',
    ];

    protected $casts = ['unit_cost' => 'decimal:2'];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function purchaseReturnItems()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function getQtyReturnedAcceptedAttribute(): int
    {
        if ($this->relationLoaded('purchaseReturnItems')) {
            return (int) $this->purchaseReturnItems
                ->where('source_type', 'accepted')
                ->sum('qty');
        }

        return (int) $this->purchaseReturnItems()
            ->where('source_type', 'accepted')
            ->sum('qty');
    }

    public function getQtyReturnedRejectedAttribute(): int
    {
        if ($this->relationLoaded('purchaseReturnItems')) {
            return (int) $this->purchaseReturnItems
                ->where('source_type', 'rejected')
                ->sum('qty');
        }

        return (int) $this->purchaseReturnItems()
            ->where('source_type', 'rejected')
            ->sum('qty');
    }

    /**
     * Qty retur 'accepted' yang induk PurchaseReturn-nya sudah berstatus 'completed'.
     * Hanya retur yang fisiknya sudah benar-benar keluar gudang yang mengurangi kuota invoice.
     */
    public function getQtyReturnedCompletedAcceptedAttribute(): int
    {
        return (int) $this->purchaseReturnItems()
            ->where('source_type', 'accepted')
            ->whereHas('purchaseReturn', fn($q) => $q->where('status', 'completed'))
            ->sum('qty');
    }

    public function getQtyAvailableForReturnAcceptedAttribute(): int
    {
        return max(0, $this->qty_received - $this->qty_returned_accepted);
    }

    public function getQtyAvailableForReturnRejectedAttribute(): int
    {
        return max(0, $this->qty_rejected - $this->qty_returned_rejected);
    }

    /**
     * Catatan: Dipertahankan untuk kompatibilitas data lama (pola Opsi A).
     * Untuk pembuatan invoice baru (aturan 1 LPB = 1 Invoice), penanda ketersediaan
     * berada di level header GoodsReceipt via field `is_invoiced`.
     */
    public function getQtyAvailableForInvoiceAttribute(): int
    {
        return max(0, $this->qty_received - ($this->invoiced_qty ?? 0) - $this->qty_returned_completed_accepted);
    }
}

