<?php

namespace App\Enums;

enum ImplementationStatus: string
{
    case NotStarted = 'not_started';
    case Ongoing = 'ongoing';
    case Operational = 'operational';
    case Completed = 'completed';
    case Suspended = 'suspended';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not Started',
            self::Ongoing => 'Ongoing',
            self::Operational => 'Operational',
            self::Completed => 'Completed',
            self::Suspended => 'Suspended',
            self::Terminated => 'Terminated',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Operational, self::Completed => 'success',
            self::Ongoing => 'info',
            self::Suspended => 'warning',
            self::Terminated => 'danger',
            self::NotStarted => 'neutral',
        };
    }
}
