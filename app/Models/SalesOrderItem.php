<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrderItem extends Model
{
    protected $appends = [
        'qty_delivered',
        'qty_remaining',
        'qty_invoiced',
        'qty_unbilled',
    ];

    protected $fillable = [
        'sales_order_id', 'product_id', 'qty_ordered',
        'unit_price', 'discount_percent', 'discount_amount', 'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function deliveryItems(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    /** Total qty yang sudah berhasil dikirim ke customer */
    public function getQtyDeliveredAttribute(): int
    {
        if ($this->relationLoaded('deliveryItems')) {
            return (int) $this->deliveryItems->sum('qty_delivered');
        }
        return (int) $this->deliveryItems()->sum('qty_delivered');
    }

    /** Sisa kewajiban kirim ke customer */
    public function getQtyRemainingAttribute(): int
    {
        return max(0, $this->qty_ordered - $this->qty_delivered);
    }

    /** Total qty terkirim yang sudah pernah diterbitkan fakturnya */
    public function getQtyInvoicedAttribute(): int
    {
        if ($this->relationLoaded('deliveryItems')) {
            return (int) $this->deliveryItems->sum('invoiced_qty');
        }
        return (int) $this->deliveryItems()->sum('invoiced_qty');
    }

    /** Qty terkirim yang BELUM pernah di-invoice (kuota tagihan yang tersedia saat ini) */
    public function getQtyUnbilledAttribute(): int
    {
        return max(0, $this->qty_delivered - $this->qty_invoiced);
    }
}
