<?php

namespace App\Enums;

enum ProjectWorkflowAction: string
{
    case Registered = 'registered';
    case Started = 'started';
    case Advanced = 'advanced';
    case Approved = 'approved';
    case Returned = 'returned';
    case Rejected = 'rejected';
    case Note = 'note';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Registered in Workflow',
            self::Started => 'Processing Started',
            self::Advanced => 'Advanced to Next Stage',
            self::Approved => 'Project Approved',
            self::Returned => 'Returned for Correction',
            self::Rejected => 'Project Rejected',
            self::Note => 'Workflow Note',
        };
    }
}
