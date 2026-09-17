<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'pr_number',
        'user_id',
        'division',
        'category_id',
        'warehouse_id',
        'request_date',
        'required_date',
        'expired_date',
        'status',
        'notes',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'request_date'  => 'date',
        'required_date' => 'date',
        'expired_date'  => 'date',
        'approved_at'   => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'purchase_request_id');
    }

    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeAvailableForPo($query)
    {
        return $query->where('status', 'approved')
            ->whereHas('items', function ($q) {
                $q->whereRaw('qty > qty_ordered');
            });
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'            => 'Draft',
            'waiting_approval' => 'Menunggu Approval',
            'approved'         => 'Approved',
            'rejected'         => 'Ditolak',
            'ordered'          => 'Sudah di-PO',
            'cancelled'        => 'Dibatalkan',
            default            => ucfirst($this->status),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft'            => 'badge-secondary',
            'waiting_approval' => 'badge-warning',
            'approved'         => 'badge-info',
            'rejected'         => 'badge-danger',
            'ordered'          => 'badge-success',
            'cancelled'        => 'badge-dark',
            default            => 'badge-secondary',
        };
    }
}