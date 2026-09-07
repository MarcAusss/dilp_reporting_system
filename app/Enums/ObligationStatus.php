<?php

namespace App\Enums;

enum ObligationStatus: string
{
    case Pending = 'pending';
    case Obligated = 'obligated';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Obligated => 'Obligated',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Obligated => 'success',
            self::Cancelled => 'danger',
            self::Pending => 'warning',
        };
    }
}
