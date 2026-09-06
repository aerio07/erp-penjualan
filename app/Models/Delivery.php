<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    protected $fillable = [
        'delivery_number', 'sales_order_id', 'warehouse_id',
        'user_id', 'status', 'condition_status', 'is_invoiced', 'sales_invoice_id',
        'delivery_date', 'received_date', 'shipping_address', 'recipient_name', 'receipt_notes', 'notes',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'received_date' => 'date',
        'is_invoiced'   => 'boolean',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    public function salesReturns(): HasMany
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function getIsAvailableForInvoiceAttribute(): bool
    {
        return !$this->is_invoiced && $this->status === 'delivered';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'in_transit' => 'Dalam Pengiriman',
            'delivered'  => 'Telah Diterima',
            'cancelled'  => 'Dibatalkan',
            default      => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'in_transit' => 'badge-pending',
            'delivered'  => 'badge-confirmed',
            'cancelled'  => 'badge-cancelled',
            default      => 'badge-neutral',
        };
    }
}
