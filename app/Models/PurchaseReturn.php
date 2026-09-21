<?php

namespace App\Models;

use App\Enums\PurchaseReturnStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    protected $fillable = [
        'return_number', 'goods_receipt_id', 'supplier_id',
        'reason', 'status', 'return_date', 'notes',
    ];

    protected $casts = [
        'return_date' => 'date',
        'status'      => PurchaseReturnStatus::class,
    ];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status instanceof PurchaseReturnStatus ? $this->status->label() : (string) $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->status instanceof PurchaseReturnStatus ? $this->status->badge() : 'badge-secondary';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status_badge;
    }

    public function isDraft(): bool
    {
        return $this->status === PurchaseReturnStatus::Draft;
    }

    public function isSent(): bool
    {
        return $this->status === PurchaseReturnStatus::Sent;
    }

    public function isCompleted(): bool
    {
        return $this->status === PurchaseReturnStatus::Completed;
    }
}
