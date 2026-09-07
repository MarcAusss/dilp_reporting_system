<?php

namespace App\Enums;

enum DataImportStatus: string
{
    case Uploaded = 'uploaded';
    case Validating = 'validating';
    case Ready = 'ready';
    case HasErrors = 'has_errors';
    case Importing = 'importing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Uploaded => 'Uploaded',
            self::Validating => 'Validating',
            self::Ready => 'Ready to Import',
            self::HasErrors => 'Needs Review',
            self::Importing => 'Importing',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Ready => 'success',
            self::Completed => 'success',
            self::HasErrors => 'warning',
            self::Failed => 'danger',
            self::Cancelled => 'danger',
            self::Validating, self::Importing => 'info',
            default => 'secondary',
        };
    }
}
