<?php

namespace App\Enums;

enum ProjectDocumentCategory: string
{
    case Proposal = 'proposal';
    case Approval = 'approval';
    case Procurement = 'procurement';
    case Financial = 'financial';
    case Insurance = 'insurance';
    case Monitoring = 'monitoring';
    case Compliance = 'compliance';
    case Replacement = 'replacement';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Proposal => 'Proposal / Application',
            self::Approval => 'Approval / Endorsement',
            self::Procurement => 'Procurement',
            self::Financial => 'Financial',
            self::Insurance => 'Insurance',
            self::Monitoring => 'Monitoring',
            self::Compliance => 'Compliance / Report',
            self::Replacement => 'Replacement',
            self::Other => 'Other',
        };
    }
}
