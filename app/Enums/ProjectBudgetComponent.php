<?php

namespace App\Enums;

enum ProjectBudgetComponent: string
{
    case EquipmentMaterialsTools = 'equipment_materials_tools';
    case Insurance = 'insurance';
    case Training = 'training';
    case ProponentPartnerEquity = 'proponent_partner_equity';
    case BeneficiaryEquity = 'beneficiary_equity';

    public function label(): string
    {
        return match ($this) {
            self::EquipmentMaterialsTools => 'Equipment / Materials / Tools',
            self::Insurance => 'Insurance',
            self::Training => 'Training',
            self::ProponentPartnerEquity => 'Proponent / Partner Equity',
            self::BeneficiaryEquity => 'Beneficiary Equity',
        };
    }

    public function financialColumn(): string
    {
        return $this->value;
    }

    public function groupLabel(): string
    {
        return match ($this) {
            self::EquipmentMaterialsTools,
            self::Insurance,
            self::Training => 'DOLE Share',

            self::ProponentPartnerEquity,
            self::BeneficiaryEquity => 'Equity',
        };
    }
}
