<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    protected $appends = [
        'qty_received',
        'qty_rejected',
        'qty_arrived',
        'qty_remaining',
        'qty_invoiced',
        'qty_unbilled',
    ];

    protected $fillable = [
        'purchase_order_id', 'product_id', 'qty_ordered',
        'unit_price', 'discount_percent', 'discount_amount', 'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function goodsReceiptItems(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    /** Hitung total qty yang sudah diterima lolos QC (kondisi baik / masuk stok) */
    public function getQtyReceivedAttribute(): int
    {
        if ($this->relationLoaded('goodsReceiptItems')) {
            return (int) $this->goodsReceiptItems->sum('qty_received');
        }
        return (int) $this->goodsReceiptItems()->sum('qty_received');
    }

    /** Hitung total qty yang rusak / reject saat penerimaan fisik */
    public function getQtyRejectedAttribute(): int
    {
        if ($this->relationLoaded('goodsReceiptItems')) {
            return (int) $this->goodsReceiptItems->sum('qty_rejected');
        }
        return (int) $this->goodsReceiptItems()->sum('qty_rejected');
    }

    /** Total qty yang secara fisik sudah tiba dari supplier (kondisi baik + kondisi rusak) */
    public function getQtyArrivedAttribute(): int
    {
        return $this->qty_received + $this->qty_rejected;
    }

    /** Sisa PO yang belum dikirim oleh supplier (kewajiban kirim) */
    public function getQtyRemainingAttribute(): int
    {
        return max(0, $this->qty_ordered - $this->qty_arrived);
    }

    /** Total qty lolos QC yang sudah pernah diterbitkan invoice-nya */
    public function getQtyInvoicedAttribute(): int
    {
        if ($this->relationLoaded('goodsReceiptItems')) {
            return (int) $this->goodsReceiptItems->sum('invoiced_qty');
        }
        return (int) $this->goodsReceiptItems()->sum('invoiced_qty');
    }

    /** Qty lolos QC yang BELUM pernah di-invoice (kuota tagihan yang tersedia saat ini) */
    public function getQtyUnbilledAttribute(): int
    {
        return max(0, $this->qty_received - $this->qty_invoiced);
    }
}
