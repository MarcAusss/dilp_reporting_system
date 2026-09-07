<?php

namespace App\Enums;

enum ReportType: string
{
    case Summary = 'summary';
    case PerFund = 'per-fund';
    case PerPo = 'per-po';
    case OnProcessRo = 'on-process-ro';
    case Sprs = 'sprs';
    case Cqpr = 'cqpr';
    case Pcl = 'pcl';
    case Raod = 'raod';

    public function label(): string
    {
        return match ($this) {
            self::Summary => 'Summary',
            self::PerFund => 'Per Fund',
            self::PerPo => 'Per PO',
            self::OnProcessRo => 'On Process at RO',
            self::Sprs => 'SPRS',
            self::Cqpr => 'CQPR',
            self::Pcl => 'PCL',
            self::Raod => 'RAOD',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Summary => 'Consolidated project, beneficiary, financial, workflow, and implementation summary.',
            self::PerFund => 'Fund-source summary of projects, beneficiaries, assistance, obligation, and disbursement.',
            self::PerPo => 'Provincial/Field Office performance summary for DILP implementation and utilization.',
            self::OnProcessRo => 'Projects still undergoing Regional Office processing and their current workflow assignment.',
            self::Sprs => 'Project status register covering approval, implementation, accomplishment, and current processing status.',
            self::Cqpr => 'Quarterly progress summary of projects, beneficiaries, financial utilization, and accomplishment.',
            self::Pcl => 'Project compliance listing for documentary requirements, reports, and unresolved monitoring findings.',
            self::Raod => 'Financial report of assistance, obligations, disbursements, balances, and utilization.',
        };
    }

    public function shortCode(): string
    {
        return strtoupper(str_replace('-', '_', $this->value));
    }
}
