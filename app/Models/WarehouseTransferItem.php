<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseTransferItem extends Model
{
    protected $fillable = [
        'warehouse_transfer_id', 'product_id',
        'qty_requested', 'qty_received', 'condition',
    ];

    public function warehouseTransfer(): BelongsTo
    {
        return $this->belongsTo(WarehouseTransfer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
