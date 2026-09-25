<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebitNoteInvoice extends Model
{
    protected $fillable = [
        'debit_note_id',
        'purchase_invoice_id',
        'sales_invoice_id',
        'invoice_number',
        'delivery_order_number',
        'invoice_date',
        'remaining_amount',
        'paid_amount',
        'dn_amount',
        'balance_amount',
    ];

    protected $casts = [
        'invoice_date'     => 'date',
        'remaining_amount' => 'decimal:2',
        'paid_amount'      => 'decimal:2',
        'dn_amount'        => 'decimal:2',
        'balance_amount'   => 'decimal:2',
    ];

    public function debitNote(): BelongsTo
    {
        return $this->belongsTo(DebitNote::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }
}
