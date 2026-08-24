<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReservation extends Model
{
    protected $fillable = [
        'sales_order_item_id',
        'warehouse_id',
        'qty_reserved',
        'qty_delivered',
        'status',
    ];

    protected $casts = [
        'qty_reserved' => 'integer',
        'qty_delivered' => 'integer',
    ];

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function getQtyActiveAttribute(): int
    {
        return max(0, $this->qty_reserved - $this->qty_delivered);
    }
}
