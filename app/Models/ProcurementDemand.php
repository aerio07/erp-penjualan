<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementDemand extends Model
{
    protected $fillable = [
        'demand_number',
        'sales_order_id',
        'sales_order_item_id',
        'product_id',
        'warehouse_id',
        'qty_demanded',
        'qty_procured',
        'qty_fulfilled',
        'purchase_order_id',
        'status',
        'required_date',
        'notes',
    ];

    protected $casts = [
        'qty_demanded' => 'integer',
        'qty_procured' => 'integer',
        'qty_fulfilled' => 'integer',
        'required_date' => 'date',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function getQtyUnfulfilledAttribute(): int
    {
        return max(0, $this->qty_demanded - $this->qty_fulfilled);
    }

    public function getQtyUnprocuredAttribute(): int
    {
        return max(0, $this->qty_demanded - $this->qty_procured);
    }
}
