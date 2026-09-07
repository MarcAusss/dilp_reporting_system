<?php

namespace App\Enums;

enum DataImportRowStatus: string
{
    case Pending = 'pending';
    case Valid = 'valid';
    case Warning = 'warning';
    case Error = 'error';
    case Duplicate = 'duplicate';
    case Imported = 'imported';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Valid => 'Valid',
            self::Warning => 'Warning',
            self::Error => 'Error',
            self::Duplicate => 'Duplicate',
            self::Imported => 'Imported',
            self::Skipped => 'Skipped',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Valid, self::Imported => 'success',
            self::Warning => 'warning',
            self::Error => 'danger',
            self::Duplicate => 'warning',
            self::Skipped => 'secondary',
            default => 'secondary',
        };
    }
}
