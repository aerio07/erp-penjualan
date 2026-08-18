<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockDisposition extends Model
{
    protected $fillable = [
        'disposition_number',
        'sales_return_item_id',
        'product_id',
        'warehouse_id',
        'qty',
        'resolution_type',
        'unit_cost',
        'sale_price',
        'journal_entry_id',
        'user_id',
        'disposed_at',
        'notes',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'disposed_at' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function salesReturnItem(): BelongsTo
    {
        return $this->belongsTo(SalesReturnItem::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
