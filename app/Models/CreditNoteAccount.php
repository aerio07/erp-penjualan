<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNoteAccount extends Model
{
    protected $fillable = [
        'credit_note_id',
        'invoice_number',
        'debit_chart_of_account_id',
        'credit_chart_of_account_id',
        'outstanding_amount',
        'cn_amount',
        'tax_amount',
        'notes',
    ];

    protected $casts = [
        'outstanding_amount' => 'decimal:2',
        'cn_amount'          => 'decimal:2',
        'tax_amount'         => 'decimal:2',
    ];

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'debit_chart_of_account_id');
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'credit_chart_of_account_id');
    }
}
