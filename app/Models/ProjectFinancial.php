<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFinancial extends Model
{
    protected $fillable = [
        'project_id',
        'equipment_materials_tools',
        'insurance',
        'training',
        'proponent_partner_equity',
        'beneficiary_equity',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'equipment_materials_tools' => 'decimal:2',
            'insurance' => 'decimal:2',
            'training' => 'decimal:2',
            'proponent_partner_equity' => 'decimal:2',
            'beneficiary_equity' => 'decimal:2',
        ];
    }

    public function doleShare(): float
    {
        return round(
            (float) $this->equipment_materials_tools
            + (float) $this->insurance
            + (float) $this->training,
            2
        );
    }

    public function totalEquity(): float
    {
        return round(
            (float) $this->proponent_partner_equity
            + (float) $this->beneficiary_equity,
            2
        );
    }

    public function totalProjectCost(): float
    {
        return round(
            $this->doleShare()
            + $this->totalEquity(),
            2
        );
    }

    public function equityPercentage(): float
    {
        $totalProjectCost =
            $this->totalProjectCost();

        if ($totalProjectCost <= 0) {
            return 0;
        }

        return round(
            (
                $this->totalEquity()
                / $totalProjectCost
            ) * 100,
            2
        );
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }
}