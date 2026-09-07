<?php

namespace App\Enums;

enum MonitoringVisitStatus: string
{
    case Scheduled = 'scheduled';
    case Conducted = 'conducted';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Conducted => 'Conducted',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Completed => 'success',
            self::Conducted => 'info',
            self::Scheduled => 'warning',
            self::Cancelled => 'danger',
        };
    }
}
