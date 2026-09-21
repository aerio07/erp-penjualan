<?php

namespace App\Models;

use App\Enums\SalesOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SalesOrder extends Model
{
    protected $fillable = [
        'so_number', 'customer_id', 'channel', 'user_id', 'status',
        'fulfillment_status', 'order_date', 'expected_delivery_date',
        'discount_amount', 'tax_rate', 'tax_amount', 'total_amount', 'notes',
        'driver_name', 'vehicle_number', 'expedition',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => SalesOrderStatus::class,
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    public function procurementDemands(): HasMany
    {
        return $this->hasMany(ProcurementDemand::class);
    }

    public function reservations(): HasManyThrough
    {
        return $this->hasManyThrough(StockReservation::class, SalesOrderItem::class);
    }

    public function deliveryItems(): HasManyThrough
    {
        return $this->hasManyThrough(DeliveryItem::class, Delivery::class);
    }

    public function invoiceItems(): HasManyThrough
    {
        return $this->hasManyThrough(SalesInvoiceItem::class, SalesInvoice::class);
    }

    public function invoicePayments(): HasManyThrough
    {
        return $this->hasManyThrough(SalesPayment::class, SalesInvoice::class);
    }

    public function returns(): HasManyThrough
    {
        return $this->hasManyThrough(SalesReturn::class, Delivery::class, 'sales_order_id', 'delivery_id');
    }

    public function getActiveReservedQtyAttribute(): int
    {
        return (int) $this->reservations()->where('stock_reservations.status', 'active')->sum('qty_reserved')
            - (int) $this->reservations()->where('stock_reservations.status', 'active')->sum('qty_delivered');
    }

    public function canCreateDelivery(): bool
    {
        if (!$this->status->canCreateDelivery()) {
            return false;
        }

        if (in_array($this->fulfillment_status, ['delivered', 'cancelled'])) {
            return false;
        }

        // Must have remaining items to deliver
        if ($this->relationLoaded('items')) {
            $hasRemaining = $this->items->sum('qty_remaining') > 0;
        } else {
            $hasRemaining = $this->items()->get()->sum('qty_remaining') > 0;
        }

        if (!$hasRemaining) {
            return false;
        }

        // Must have at least 1 unit reserved in stock or status is ready/partial
        return in_array($this->fulfillment_status, ['ready_to_ship', 'partially_available', 'partially_delivered'])
            || $this->active_reserved_qty > 0;
    }

    public function canCreatePartialDelivery(): bool
    {
        return $this->canCreateDelivery() && in_array($this->fulfillment_status, ['partially_available', 'partially_delivered']);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status instanceof SalesOrderStatus ? $this->status->label() : (string) $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->status instanceof SalesOrderStatus ? $this->status->badge() : 'badge-secondary';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status_badge;
    }

    public function isDraft(): bool
    {
        return $this->status === SalesOrderStatus::Draft;
    }

    public function isWaitingApproval(): bool
    {
        return $this->status === SalesOrderStatus::WaitingApproval;
    }

    public function isConfirmed(): bool
    {
        return $this->status === SalesOrderStatus::Confirmed;
    }

    public function isCancelled(): bool
    {
        return $this->status === SalesOrderStatus::Cancelled;
    }
}
