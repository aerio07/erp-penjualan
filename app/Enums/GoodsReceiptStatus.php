<?php

namespace App\Enums;

enum GoodsReceiptStatus: string
{
    case Draft           = 'draft';
    case WaitingApproval = 'waiting_approval';
    case Confirmed       = 'confirmed';
    case Revised         = 'revised';
    case Rejected        = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft           => 'Draft',
            self::WaitingApproval => 'Menunggu Approval',
            self::Confirmed       => 'Disetujui',
            self::Revised         => 'Perlu Revisi',
            self::Rejected        => 'Ditolak',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Draft           => 'badge-pending',
            self::WaitingApproval => 'badge-warning',
            self::Confirmed       => 'badge-done',
            self::Revised         => 'badge-warning',
            self::Rejected        => 'badge-cancelled',
        };
    }

    /** Apakah LPB sudah disetujui dan stok sudah masuk gudang? */
    public function isApproved(): bool
    {
        return $this === self::Confirmed;
    }

    /** Apakah LPB masih bisa diedit? */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Revised]);
    }
}
