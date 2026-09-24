<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashBankExpenseItem extends Model
{
    protected $fillable = [
        'cash_bank_expense_id', 'chart_of_account_id',
        'description', 'amount', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(CashBankExpense::class, 'cash_bank_expense_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }
}
