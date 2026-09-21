<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case InTransit  = 'in_transit';
    case Delivered  = 'delivered';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::InTransit => 'Dalam Pengiriman',
            self::Delivered => 'Telah Diterima',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::InTransit => 'badge-pending',
            self::Delivered => 'badge-confirmed',
            self::Cancelled => 'badge-cancelled',
        };
    }

    /** Apakah SJ ini sudah bisa dibuatkan invoice? */
    public function canBeInvoiced(): bool
    {
        return $this === self::Delivered;
    }
}
