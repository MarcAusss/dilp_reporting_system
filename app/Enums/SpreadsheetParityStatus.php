<?php

namespace App\Enums;

enum SpreadsheetParityStatus: string
{
    case Covered = 'covered';
    case Partial = 'partial';
    case Missing = 'missing';
    case Derived = 'derived';
    case Archive = 'archive';

    public function label(): string
    {
        return match ($this) {
            self::Covered => 'Covered',
            self::Partial => 'Partial',
            self::Missing => 'Missing',
            self::Derived => 'System Calculated',
            self::Archive => 'Archive / Reference',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Covered => 'success',
            self::Partial => 'warning',
            self::Missing => 'danger',
            self::Derived => 'info',
            self::Archive => 'default',
        };
    }
}
