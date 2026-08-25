<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case RegionalTssd = 'regional_tssd';
    case ProvincialOffice = 'provincial_office';
    case Evaluator = 'evaluator';
    case ImsdProcurement = 'imsd_procurement';
    case ReportingMe = 'reporting_me';
    case ManagementViewer = 'management_viewer';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::RegionalTssd => 'Regional / TSSD User',
            self::ProvincialOffice => 'Provincial Office User',
            self::Evaluator => 'Evaluator',
            self::ImsdProcurement => 'IMSD / Procurement User',
            self::ReportingMe => 'Reporting / M&E User',
            self::ManagementViewer => 'Management Viewer',
        };
    }
}