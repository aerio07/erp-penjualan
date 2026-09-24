<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashBankReceipt extends Model
{
    protected $fillable = [
        'company_name', 'receipt_number', 'voucher_number', 'type',
        'transaction_date', 'group', 'chart_of_account_id',
        'reference_number', 'notes', 'document_file', 'amount',
        'status', 'created_by', 'posted_by', 'posted_at',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'posted_at'        => 'datetime',
        'amount'           => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CashBankReceiptItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function isDraft(): bool { return $this->status === 'draft'; }
    public function isPosted(): bool { return $this->status === 'posted'; }
}
