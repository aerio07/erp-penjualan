<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DebitNote extends Model
{
    protected $fillable = [
        'draft_number',
        'dn_number',
        'dn_type',
        'business_unit',
        'supplier_id',
        'customer_id',
        'dn_date',
        'tax_type',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'status',
        'notes',
        'journal_entry_id',
        'created_by',
        'approved_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'dn_date'      => 'date',
        'tax_rate'     => 'decimal:2',
        'tax_amount'   => 'decimal:2',
        'total_amount' => 'decimal:2',
        'posted_at'    => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(DebitNoteInvoice::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(DebitNoteAccount::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function getPartnerNameAttribute(): string
    {
        if ($this->dn_type === 'AP') {
            return $this->supplier ? $this->supplier->name : '-';
        }
        return $this->customer ? $this->customer->name : '-';
    }

    public function getInvoiceNumbersSummaryAttribute(): string
    {
        if ($this->relationLoaded('invoices')) {
            return $this->invoices->pluck('invoice_number')->filter()->unique()->implode(', ') ?: '-';
        }
        return $this->invoices()->pluck('invoice_number')->filter()->unique()->implode(', ') ?: '-';
    }

    public function getCreditAccountSummaryAttribute(): string
    {
        $accounts = $this->relationLoaded('accounts') ? $this->accounts : $this->accounts()->with('creditAccount')->get();
        $names = $accounts->map(function ($acc) {
            return $acc->creditAccount ? $acc->creditAccount->name : null;
        })->filter()->unique()->values();

        return $names->isNotEmpty() ? $names->implode(', ') : '-';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'Draft',
            'posted'    => 'Posted',
            'cancelled' => 'Dibatalkan',
            default     => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'badge-secondary',
            'posted'    => 'badge-done',
            'cancelled' => 'badge-danger',
            default     => 'badge-secondary',
        };
    }
}
