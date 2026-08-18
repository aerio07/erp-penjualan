<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    protected $fillable = [
        'entry_number', 'entry_date', 'reference_type', 'reference_id',
        'description', 'status', 'created_by', 'posted_by', 'posted_at',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'posted_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function getTotalDebitAttribute(): float
    {
        return (float) $this->lines()->sum('debit');
    }

    public function getTotalCreditAttribute(): float
    {
        return (float) $this->lines()->sum('credit');
    }

    public function isBalanced(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }
}
