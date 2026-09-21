<?php

namespace App\Models;

use App\Enums\PurchaseRequestStatus;
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
        'status'        => PurchaseRequestStatus::class,
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
        return $query->whereIn('status', [
            PurchaseRequestStatus::Confirmed->value,
        ]);
    }

    public function scopeAvailableForPo($query)
    {
        return $query->whereIn('status', [
            PurchaseRequestStatus::Confirmed->value,
            PurchaseRequestStatus::Done->value,
        ])->whereHas('items', function ($q) {
            $q->whereRaw('qty > qty_ordered');
        });
    }

    /**
     * Accessor untuk label teks status — mendelegasikan ke Enum.
     * Mempertahankan logika khusus: jika draft tapi approval terakhir 'revised', tampilkan 'Revisi'.
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->status === PurchaseRequestStatus::Draft) {
            $latest = $this->approvalRequests->sortByDesc('id')->first();
            if ($latest && $latest->status === 'revised') {
                return PurchaseRequestStatus::Revised->label();
            }
        }

        return $this->status->label();
    }

    /**
     * Accessor untuk CSS badge class status — mendelegasikan ke Enum.
     */
    public function getStatusBadgeAttribute(): string
    {
        if ($this->status === PurchaseRequestStatus::Draft) {
            $latest = $this->approvalRequests->sortByDesc('id')->first();
            if ($latest && $latest->status === 'revised') {
                return PurchaseRequestStatus::Revised->badge();
            }
        }

        return $this->status->badge();
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status_badge;
    }

    public function isDraft(): bool
    {
        return $this->status === PurchaseRequestStatus::Draft;
    }

    public function isWaitingApproval(): bool
    {
        return $this->status === PurchaseRequestStatus::WaitingApproval;
    }

    public function isConfirmed(): bool
    {
        return $this->status === PurchaseRequestStatus::Confirmed;
    }

    public function isDone(): bool
    {
        return $this->status === PurchaseRequestStatus::Done;
    }

    public function isRevised(): bool
    {
        return $this->status === PurchaseRequestStatus::Revised;
    }

    public function isRejected(): bool
    {
        return $this->status === PurchaseRequestStatus::Rejected;
    }
}