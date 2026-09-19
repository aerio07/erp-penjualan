<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class GoodsReceipt extends Model
{
    protected $fillable = [
        'receipt_number', 'purchase_order_id', 'warehouse_id',
        'user_id', 'qc_status', 'status', 'is_invoiced', 'purchase_invoice_id',
        'received_date', 'notes', 'approved_by', 'approved_at', 'rejection_reason',
        'auto_generate_invoice', 'supplier_invoice_number', 'invoice_date', 'due_date', 'invoice_tax_rate',
    ];

    protected $casts = [
        'received_date'         => 'date',
        'is_invoiced'           => 'boolean',
        'approved_at'           => 'datetime',
        'auto_generate_invoice' => 'boolean',
        'invoice_date'          => 'date',
        'due_date'              => 'date',
        'invoice_tax_rate'      => 'decimal:2',
    ];

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft'            => 'Draft',
            'waiting_approval' => 'Menunggu Approval',
            'confirmed'        => 'Disetujui',
            'rejected'         => 'Ditolak',
            'revised'          => 'Perlu Revisi',
            default            => ucfirst($this->status ?? 'Draft'),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'draft'            => 'badge-pending',
            'waiting_approval' => 'badge-warning',
            'confirmed'        => 'badge-done',
            'rejected'         => 'badge-cancelled',
            'revised'          => 'badge-warning',
            default            => 'badge-secondary',
        };
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    /**
     * Apakah LPB ini masih bisa dipilih untuk invoice baru?
     * true jika belum pernah dipakai, false jika sudah.
     */
    public function getIsAvailableForInvoiceAttribute(): bool
    {
        return !$this->is_invoiced;
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
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function getWarehouseNamesAttribute(): string
    {
        if ($this->relationLoaded('items')) {
            $names = $this->items->map(fn($item) => $item->warehouse?->name)->filter()->unique();
            if ($names->isNotEmpty()) {
                return $names->implode(', ');
            }
        }
        return $this->warehouse?->name ?? '-';
    }
}
