<?php

namespace App\Enums;

enum SalesInvoiceStatus: string
{
    case Draft   = 'draft';
    case Unpaid  = 'unpaid';
    case Partial = 'partial';
    case Paid    = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Draft   => 'Draft',
            self::Unpaid  => 'Belum Dibayar',
            self::Partial => 'Dibayar Sebagian',
            self::Paid    => 'Lunas',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Draft   => 'badge-secondary',
            self::Unpaid  => 'badge-danger',
            self::Partial => 'badge-warning',
            self::Paid    => 'badge-success',
        };
    }

    public function isDraft(): bool
    {
        return $this === self::Draft;
    }

    /** Apakah invoice sudah di-post (sudah membentuk jurnal akuntansi)? */
    public function isPosted(): bool
    {
        return in_array($this, [self::Unpaid, self::Partial, self::Paid]);
    }

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }
}
