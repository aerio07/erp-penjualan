<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = ['code', 'name', 'address', 'is_active', 'notes'];

    protected $casts = ['is_active' => 'boolean'];

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function stockOpnames(): HasMany
    {
        return $this->hasMany(StockOpname::class);
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(WarehouseTransfer::class, 'from_warehouse_id');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(WarehouseTransfer::class, 'to_warehouse_id');
    }
}
