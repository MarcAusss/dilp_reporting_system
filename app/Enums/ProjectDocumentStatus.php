<?php

namespace App\Enums;

enum ProjectDocumentStatus: string
{
    case Missing = 'missing';
    case Pending = 'pending';
    case Submitted = 'submitted';
    case Verified = 'verified';
    case Returned = 'returned';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Missing => 'Missing',
            self::Pending => 'Pending',
            self::Submitted => 'Submitted',
            self::Verified => 'Verified',
            self::Returned => 'Returned',
            self::Expired => 'Expired',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Verified => 'success',
            self::Submitted => 'info',
            self::Pending => 'warning',
            self::Missing, self::Returned, self::Expired => 'danger',
        };
    }
}
