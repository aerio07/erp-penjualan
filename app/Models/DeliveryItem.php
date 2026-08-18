<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryItem extends Model
{
    protected $appends = [
        'qty_available_for_invoice',
        'qty_returned',
        'qty_returned_completed',
        'qty_available_for_return',
    ];

    protected $fillable = [
        'delivery_id', 'sales_order_item_id',
        'qty_delivered', 'invoiced_qty', 'condition',
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function salesReturnItems(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
    }

    /** Qty terkirim yang belum pernah di-invoice, dikurangi retur yang sudah diterima/selesai */
    public function getQtyAvailableForInvoiceAttribute(): int
    {
        return max(0, $this->qty_delivered - ($this->invoiced_qty ?? 0) - $this->qty_returned_completed);
    }

    /** Total qty yang sudah pernah diajukan retur dari delivery ini (semua status) */
    public function getQtyReturnedAttribute(): int
    {
        if ($this->relationLoaded('salesReturnItems')) {
            return (int) $this->salesReturnItems->sum('qty');
        }
        return (int) $this->salesReturnItems()->sum('qty');
    }

    /**
     * Total qty retur yang induk SalesReturn-nya sudah 'received' atau 'completed'.
     * Barang ini sudah secara fisik kembali ke gudang, jadi tidak layak ditagih lagi.
     */
    public function getQtyReturnedCompletedAttribute(): int
    {
        return (int) $this->salesReturnItems()
            ->whereHas('salesReturn', fn($q) => $q->whereIn('status', ['received', 'completed']))
            ->sum('qty');
    }

    /** Sisa qty terkirim yang masih bisa diretur */
    public function getQtyAvailableForReturnAttribute(): int
    {
        return max(0, $this->qty_delivered - $this->qty_returned);
    }
}

