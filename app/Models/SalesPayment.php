<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class SalesPayment extends Model
{
    protected $fillable = [
        'payment_number', 'sales_invoice_id', 'chart_of_account_id', 'user_id', 'amount', 'status',
        'payment_date', 'payment_type', 'method', 'reference_number', 'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function ($payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = static::generatePaymentNumber(
                    $payment->method ?? 'transfer',
                    $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                    $payment->payment_type ?? null
                );
            }
        });
    }

    public static function generatePaymentNumber(string $method = 'transfer', ?string $date = null, ?string $paymentType = null): string
    {
        $isKas = ($paymentType === 'kas') || ($method === 'cash');
        $prefix = $isKas ? 'BKM' : 'BBM';
        $dateFormatted = $date ? Carbon::parse($date)->format('d/m/Y') : date('d/m/Y');
        $datePrefix = $prefix . '-' . $dateFormatted . '-';

        $last = static::where('payment_number', 'like', $datePrefix . '%')
            ->orderByDesc('id')
            ->value('payment_number');

        $seq = $last ? ((int) substr($last, strlen($datePrefix)) + 1) : 1;

        return $datePrefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getVoucherTypeAttribute(): string
    {
        return ($this->payment_type === 'kas' || $this->method === 'cash') ? 'BKM' : 'BBM';
    }

    public function getVoucherNumberAttribute(): string
    {
        return $this->payment_number ?: ($this->voucher_type . '-' . str_pad($this->id, 5, '0', STR_PAD_LEFT));
    }

    public function getVoucherTitleAttribute(): string
    {
        return ($this->payment_type === 'kas' || $this->method === 'cash') ? 'BUKTI KAS MASUK' : 'BUKTI BANK MASUK';
    }

    public function getVoucherLabelAttribute(): string
    {
        return ($this->payment_type === 'kas' || $this->method === 'cash') ? 'No. Bukti Kas Masuk' : 'No. Bukti Bank Masuk';
    }
}
