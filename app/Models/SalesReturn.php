<?php

namespace App\Models;

use App\Enums\SalesReturnStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReturn extends Model
{
    protected $fillable = [
        'return_number', 'delivery_id', 'customer_id',
        'reason', 'status', 'return_date', 'notes',
    ];

    protected $casts = [
        'return_date' => 'date',
        'status'      => SalesReturnStatus::class,
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status instanceof SalesReturnStatus ? $this->status->label() : (string) $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->status instanceof SalesReturnStatus ? $this->status->badge() : 'badge-secondary';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status_badge;
    }

    public function isDraft(): bool
    {
        return $this->status === SalesReturnStatus::Draft;
    }

    public function isReceived(): bool
    {
        return $this->status === SalesReturnStatus::Received;
    }

    public function isCompleted(): bool
    {
        return $this->status === SalesReturnStatus::Completed;
    }
}
