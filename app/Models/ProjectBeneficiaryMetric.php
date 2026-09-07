<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBeneficiaryMetric extends Model
{
    protected $fillable = [
        'project_id',
        'total_beneficiaries',
        'female_beneficiaries',
        'male_beneficiaries',
        'female_assistance_amount',
        'beneficiary_type_ies',
        'source',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'total_beneficiaries' => 'integer',
            'female_beneficiaries' => 'integer',
            'male_beneficiaries' => 'integer',
            'female_assistance_amount' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

}
