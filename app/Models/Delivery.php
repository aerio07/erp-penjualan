<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
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
        'status'        => DeliveryStatus::class,
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
        return !$this->is_invoiced && $this->status->canBeInvoiced();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status instanceof DeliveryStatus ? $this->status->label() : ucfirst((string) ($this->status ?? 'In Transit'));
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status instanceof DeliveryStatus ? $this->status->badge() : 'badge-secondary';
    }

    public function isInTransit(): bool
    {
        return $this->status === DeliveryStatus::InTransit;
    }

    public function isDelivered(): bool
    {
        return $this->status === DeliveryStatus::Delivered;
    }

    public function isCancelled(): bool
    {
        return $this->status === DeliveryStatus::Cancelled;
    }
}
