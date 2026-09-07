<?php

namespace App\Enums;

enum MonitoringVisitType: string
{
    case Initial = 'initial';
    case Regular = 'regular';
    case FollowUp = 'follow_up';
    case Validation = 'validation';
    case Completion = 'completion';
    case PostImplementation = 'post_implementation';

    public function label(): string
    {
        return match ($this) {
            self::Initial => 'Initial Monitoring',
            self::Regular => 'Regular Monitoring',
            self::FollowUp => 'Follow-up Monitoring',
            self::Validation => 'Validation Visit',
            self::Completion => 'Completion Monitoring',
            self::PostImplementation => 'Post-Implementation Monitoring',
        };
    }
}
