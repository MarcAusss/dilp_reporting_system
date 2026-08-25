<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case GIP = 'gip';
    case Focal = 'focal';
    case DilpCoordinator = 'dilp_coordinator';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::GIP => 'GIP',
            self::Focal => 'Focal',
            self::DilpCoordinator => 'DILP Coordinator',
        };
    }
}