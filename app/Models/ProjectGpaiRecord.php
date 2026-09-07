<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectGpaiRecord extends Model
{
    protected $fillable = [
        'project_id',
        'beneficiaries_enrolled',
        'gpai_amount',
        'enrollment_status',
        'fund_source_id',
        'forwarded_for_enrollment_at',
        'cash_advance_payee',
        'responsible_person',
        'or_policy_received_at',
        'or_number',
        'or_date',
        'policy_number',
        'dv_number',
        'check_date',
        'check_number',
        'remarks',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'beneficiaries_enrolled' => 'integer',
            'gpai_amount' => 'decimal:2',
            'forwarded_for_enrollment_at' => 'date',
            'or_policy_received_at' => 'date',
            'or_date' => 'date',
            'check_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function fundSource(): BelongsTo
    {
        return $this->belongsTo(FundSource::class);
    }

}
