<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Draft             = 'draft';
    case WaitingApproval   = 'waiting_approval';
    case Confirmed         = 'confirmed';
    case PartiallyReceived = 'partially_received';
    case Done              = 'done';
    case Cancelled         = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft             => 'Draft',
            self::WaitingApproval   => 'Menunggu Approval',
            self::Confirmed         => 'Dikonfirmasi',
            self::PartiallyReceived => 'Diterima Sebagian',
            self::Done              => 'Selesai',
            self::Cancelled         => 'Dibatalkan',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Draft             => 'badge-secondary',
            self::WaitingApproval   => 'badge-warning',
            self::Confirmed         => 'badge-info',
            self::PartiallyReceived => 'badge-warning',
            self::Done              => 'badge-success',
            self::Cancelled         => 'badge-dark',
        };
    }

    /** Apakah PO sudah bisa dibuatkan GRN? */
    public function canReceiveGoods(): bool
    {
        return in_array($this, [self::Confirmed, self::PartiallyReceived]);
    }

    /** Apakah PO sudah selesai sepenuhnya atau dibatalkan? */
    public function isClosed(): bool
    {
        return in_array($this, [self::Done, self::Cancelled]);
    }
}
