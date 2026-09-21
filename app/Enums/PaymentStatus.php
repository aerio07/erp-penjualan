<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Draft  = 'draft';
    case Posted = 'posted';

    public function label(): string
    {
        return match ($this) {
            self::Draft  => 'Draft',
            self::Posted => 'Posted',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Draft  => 'badge-secondary',
            self::Posted => 'badge-success',
        };
    }

    public function isDraft(): bool
    {
        return $this === self::Draft;
    }

    public function isPosted(): bool
    {
        return $this === self::Posted;
    }
}
