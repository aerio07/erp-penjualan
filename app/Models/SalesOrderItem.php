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
        'qty_reserved',
        'qty_demanded',
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

    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    public function procurementDemands(): HasMany
    {
        return $this->hasMany(ProcurementDemand::class);
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

    /** Qty aktif yang ter-reserve di stok fisik */
    public function getQtyReservedAttribute(): int
    {
        if ($this->relationLoaded('reservations')) {
            return (int) $this->reservations->where('status', 'active')->sum(function ($r) {
                return max(0, $r->qty_reserved - $r->qty_delivered);
            });
        }
        return (int) $this->reservations()->where('status', 'active')->sum('qty_reserved')
            - (int) $this->reservations()->where('status', 'active')->sum('qty_delivered');
    }

    /** Qty defisit yang sedang menunggu pengadaan (backorder) */
    public function getQtyDemandedAttribute(): int
    {
        if ($this->relationLoaded('procurementDemands')) {
            return (int) $this->procurementDemands->whereIn('status', ['pending', 'ordered'])->sum(function ($d) {
                return max(0, $d->qty_demanded - $d->qty_fulfilled);
            });
        }
        return (int) $this->procurementDemands()->whereIn('status', ['pending', 'ordered'])->sum('qty_demanded')
            - (int) $this->procurementDemands()->whereIn('status', ['pending', 'ordered'])->sum('qty_fulfilled');
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
