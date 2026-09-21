<?php

namespace App\Enums;

enum SalesReturnStatus: string
{
    case Draft     = 'draft';
    case Received  = 'received';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Draft',
            self::Received  => 'Barang Diterima',
            self::Completed => 'Selesai',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Draft     => 'badge-secondary',
            self::Received  => 'badge-warning',
            self::Completed => 'badge-success',
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }
}
