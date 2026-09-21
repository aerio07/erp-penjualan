<?php

namespace App\Enums;

enum SalesOrderStatus: string
{
    case Draft              = 'draft';
    case WaitingApproval    = 'waiting_approval';
    case Confirmed          = 'confirmed';
    case PartiallyDelivered = 'partially_delivered';
    case Done               = 'done';
    case Cancelled          = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft              => 'Draft',
            self::WaitingApproval    => 'Menunggu Approval',
            self::Confirmed          => 'Dikonfirmasi',
            self::PartiallyDelivered => 'Dikirim Sebagian',
            self::Done               => 'Selesai',
            self::Cancelled          => 'Dibatalkan',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Draft              => 'badge-secondary',
            self::WaitingApproval    => 'badge-warning',
            self::Confirmed          => 'badge-info',
            self::PartiallyDelivered => 'badge-warning',
            self::Done               => 'badge-success',
            self::Cancelled          => 'badge-dark',
        };
    }

    /** Apakah SO bisa dibuatkan Surat Jalan? */
    public function canCreateDelivery(): bool
    {
        return in_array($this, [self::Confirmed, self::PartiallyDelivered]);
    }

    /** Apakah SO sudah tutup (tidak bisa dimodifikasi)? */
    public function isClosed(): bool
    {
        return in_array($this, [self::Done, self::Cancelled]);
    }
}
