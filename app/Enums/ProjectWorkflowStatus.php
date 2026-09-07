<?php

namespace App\Enums;

enum ProjectWorkflowStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Returned = 'returned';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::Returned => 'Returned',
            self::Completed => 'Completed',
            self::Rejected => 'Rejected',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::InProgress => 'info',
            self::Returned => 'danger',
            self::Completed => 'success',
            self::Rejected => 'danger',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Completed,
            self::Rejected,
        ], true);
    }
}
