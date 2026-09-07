<?php

namespace App\Enums;

enum ProcurementStatus: string
{
    case Planned = 'planned';
    case ForProcurement = 'for_procurement';
    case Ongoing = 'ongoing';
    case Awarded = 'awarded';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::ForProcurement => 'For Procurement',
            self::Ongoing => 'Ongoing',
            self::Awarded => 'Awarded',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Delivered => 'success',
            self::Cancelled => 'danger',
            self::Awarded => 'info',
            self::Ongoing, self::ForProcurement => 'warning',
            self::Planned => 'neutral',
        };
    }
}
