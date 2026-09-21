<?php

namespace App\Enums;

enum PurchaseReturnStatus: string
{
    case Draft     = 'draft';
    case Sent      = 'sent';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Draft',
            self::Sent      => 'Dikirim ke Supplier',
            self::Completed => 'Selesai',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Draft     => 'badge-secondary',
            self::Sent      => 'badge-warning',
            self::Completed => 'badge-success',
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }
}
