<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebitNoteAccount extends Model
{
    protected $fillable = [
        'debit_note_id',
        'invoice_number',
        'debit_chart_of_account_id',
        'credit_chart_of_account_id',
        'outstanding_amount',
        'dn_amount',
        'tax_amount',
        'notes',
    ];

    protected $casts = [
        'outstanding_amount' => 'decimal:2',
        'dn_amount'          => 'decimal:2',
        'tax_amount'         => 'decimal:2',
    ];

    public function debitNote(): BelongsTo
    {
        return $this->belongsTo(DebitNote::class);
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
