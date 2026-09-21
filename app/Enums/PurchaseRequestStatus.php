<?php

namespace App\Enums;

enum PurchaseRequestStatus: string
{
    case Draft           = 'draft';
    case Revised         = 'revised';
    case WaitingApproval = 'waiting_approval';
    case Confirmed       = 'confirmed';
    case Done            = 'done';
    case Rejected        = 'rejected';
    case Cancelled       = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft           => 'Draft',
            self::Revised         => 'Perlu Revisi',
            self::WaitingApproval => 'Menunggu Approval',
            self::Confirmed       => 'Dikonfirmasi',
            self::Done            => 'Selesai',
            self::Rejected        => 'Ditolak',
            self::Cancelled       => 'Dibatalkan',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Draft           => 'badge-secondary',
            self::Revised         => 'badge-warning',
            self::WaitingApproval => 'badge-warning',
            self::Confirmed       => 'badge-info',
            self::Done            => 'badge-success',
            self::Rejected        => 'badge-danger',
            self::Cancelled       => 'badge-dark',
        };
    }

    /** Apakah dokumen ini masih bisa diedit / di-submit ulang? */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Revised, self::Rejected]);
    }

    /** Apakah dokumen ini sudah disetujui dan bisa dibuatkan PO? */
    public function isApprovedForPo(): bool
    {
        return in_array($this, [self::Confirmed, self::Done]);
    }

    /** Apakah dokumen ini masih menunggu tindakan approval? */
    public function isPendingApproval(): bool
    {
        return $this === self::WaitingApproval;
    }
}

