<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    protected $fillable = ['code', 'name', 'type', 'normal_balance', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
