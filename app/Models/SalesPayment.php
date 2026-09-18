<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPayment extends Model
{
    protected $fillable = [
        'sales_invoice_id', 'user_id', 'amount', 'status',
        'payment_date', 'method', 'reference_number', 'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getVoucherTypeAttribute(): string
    {
        return $this->method === 'cash' ? 'BKM' : 'BBM';
    }

    public function getVoucherNumberAttribute(): string
    {
        return $this->voucher_type . '-' . str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }

    public function getVoucherTitleAttribute(): string
    {
        return $this->method === 'cash' ? 'BUKTI KAS MASUK' : 'BUKTI BANK MASUK';
    }

    public function getVoucherLabelAttribute(): string
    {
        return $this->method === 'cash' ? 'No. Bukti Kas Masuk' : 'No. Bukti Bank Masuk';
    }
}
