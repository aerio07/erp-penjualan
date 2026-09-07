<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Supplier extends Model
{
    protected $fillable = [
        'code', 'name', 'contact_person', 'phone', 'email',
        'address', 'payment_term', 'npwp', 'ktp_number', 'bank_name', 'bank_account_number', 'bank_account_holder',
        'is_active', 'notes',
    ];

    protected $casts = ['is_active' => 'boolean'];

    protected $appends = ['payment_term_days'];

    public function getPaymentTermDaysAttribute(): int
    {
        if (empty($this->payment_term)) {
            return 30;
        }
        if (strtoupper(trim($this->payment_term)) === 'COD') {
            return 0;
        }
        if (preg_match('/\d+/', (string) $this->payment_term, $matches)) {
            return (int) $matches[0];
        }
        return 30;
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function purchaseInvoices(): HasManyThrough
    {
        return $this->hasManyThrough(PurchaseInvoice::class, PurchaseOrder::class);
    }
}
