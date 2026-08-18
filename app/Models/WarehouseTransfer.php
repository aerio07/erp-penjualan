<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseTransfer extends Model
{
    protected $fillable = [
        'transfer_number', 'from_warehouse_id', 'to_warehouse_id',
        'user_id', 'status', 'transfer_date', 'received_date', 'notes',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'received_date' => 'date',
    ];

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseTransferItem::class);
    }
}
