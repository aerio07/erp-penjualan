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
