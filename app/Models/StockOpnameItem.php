<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    protected $fillable = [
        'stock_opname_id', 'product_id',
        'system_qty', 'physical_qty', 'difference',
        'adjustment_reason', 'adjustment_note', 'notes',
    ];

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getReasonLabelAttribute(): string
    {
        return match ($this->adjustment_reason) {
            'lost'               => 'Barang Hilang',
            'damaged_unrecorded' => 'Rusak Belum Tercatat',
            'counting_error'     => 'Salah Hitung',
            'found_extra'        => 'Ditemukan Lebih',
            'other'              => 'Lainnya',
            default              => '-',
        };
    }
}
