<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashBankReceiptItem extends Model
{
    protected $fillable = [
        'cash_bank_receipt_id', 'chart_of_account_id',
        'description', 'amount', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(CashBankReceipt::class, 'cash_bank_receipt_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }
}
