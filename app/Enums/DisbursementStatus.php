<?php

namespace App\Enums;

enum DisbursementStatus: string
{
    case ForPayment = 'for_payment';
    case Processing = 'processing';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::ForPayment => 'For Payment',
            self::Processing => 'Processing',
            self::Paid => 'Paid',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Paid => 'success',
            self::Cancelled => 'danger',
            self::Processing => 'info',
            self::ForPayment => 'warning',
        };
    }
}
