<?php

namespace App\Enums;

enum ReplacementRequestStatus: string
{
    case Pending = 'pending';
    case ForReview = 'for_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::ForReview => 'For Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Approved, self::Completed => 'success',
            self::Rejected, self::Cancelled => 'danger',
            self::ForReview => 'info',
            self::Pending => 'warning',
        };
    }
}
