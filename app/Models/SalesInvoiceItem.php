<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceItem extends Model
{
    protected $fillable = [
        'sales_invoice_id',
        'sales_order_item_id',
        'delivery_item_id',
        'product_id',
        'qty_invoiced',
        'reversed_qty',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'subtotal',
        'tax_amount',
        'cogs_amount',
    ];

    protected $casts = [
        'unit_price'       => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount'  => 'decimal:2',
        'subtotal'         => 'decimal:2',
        'tax_amount'       => 'decimal:2',
        'cogs_amount'      => 'decimal:2',
    ];

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function deliveryItem(): BelongsTo
    {
        return $this->belongsTo(DeliveryItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
