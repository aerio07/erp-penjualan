<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'asset_code',
        'name',
        'acquisition_type',
        'purchase_date',
        'purchase_cost',
        'salvage_value',
        'useful_life_months',
        'depreciation_method',
        'status',
        'location',
        'serial_number',
        'notes',
        'journal_entry_id',
        'batch_reference',
        'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'useful_life_months' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FixedAssetCategory::class, 'category_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Aktif',
            'maintenance' => 'Pemeliharaan',
            'disposed' => 'Dihapus/Afkir',
            'sold' => 'Terjual',
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'active' => 'badge-confirmed',
            'maintenance' => 'badge-pending',
            'disposed' => 'badge-cancelled',
            'sold' => 'badge-neutral',
            default => 'badge-neutral',
        };
    }
}
