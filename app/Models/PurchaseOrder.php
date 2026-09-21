<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_number', 'supplier_id', 'purchase_request_id', 'warehouse_id', 'user_id', 'status',
        'order_date', 'expected_date', 'discount_amount',
        'tax_rate', 'tax_amount', 'total_amount', 'notes', 'ship_to',
        'cancel_reason',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => PurchaseOrderStatus::class,
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    public function procurementDemands(): HasMany
    {
        return $this->hasMany(ProcurementDemand::class);
    }

    public function goodsReceiptItems(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(GoodsReceiptItem::class, GoodsReceipt::class);
    }

    public function invoiceItems(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(PurchaseInvoiceItem::class, PurchaseInvoice::class);
    }

    public function invoicePayments(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(PurchasePayment::class, PurchaseInvoice::class);
    }

    public function returns(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(PurchaseReturn::class, GoodsReceipt::class, 'purchase_order_id', 'goods_receipt_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status instanceof PurchaseOrderStatus ? $this->status->label() : (string) $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->status instanceof PurchaseOrderStatus ? $this->status->badge() : 'badge-secondary';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status_badge;
    }

    public function isDraft(): bool
    {
        return $this->status === PurchaseOrderStatus::Draft;
    }

    public function isWaitingApproval(): bool
    {
        return $this->status === PurchaseOrderStatus::WaitingApproval;
    }

    public function isConfirmed(): bool
    {
        return $this->status === PurchaseOrderStatus::Confirmed;
    }

    public function isCancelled(): bool
    {
        return $this->status === PurchaseOrderStatus::Cancelled;
    }
}
