<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    protected $fillable = [
        'parent_id', 'code', 'name', 'type', 'normal_balance', 'description', 'is_active', 'is_cash_bank'
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'is_cash_bank' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id')->orderBy('code');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function getFullNameAttribute(): string
    {
        return "[{$this->code}] {$this->name}";
    }

    /**
     * Scope: ambil semua akun Kas & Bank aktif.
     * Dipakai bersama oleh ReportController::cashFlow() dan CashFlowForecast.
     */
    public function scopeCashAndBank($query)
    {
        return $query->where(function ($q) {
            $q->where('is_cash_bank', true)
              ->orWhere('code', 'LIKE', '1-11%');
        })->where('is_active', true);
    }
}
