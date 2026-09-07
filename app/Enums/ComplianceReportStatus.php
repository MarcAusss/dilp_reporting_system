<?php

namespace App\Enums;

enum ComplianceReportStatus: string
{
    case Pending = 'pending';
    case Submitted = 'submitted';
    case Verified = 'verified';
    case Returned = 'returned';
    case Waived = 'waived';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Submitted => 'Submitted',
            self::Verified => 'Verified',
            self::Returned => 'Returned',
            self::Waived => 'Waived',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Verified, self::Waived => 'success',
            self::Submitted => 'info',
            self::Pending => 'warning',
            self::Returned => 'danger',
        };
    }
}
