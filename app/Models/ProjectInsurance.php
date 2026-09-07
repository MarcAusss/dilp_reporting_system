<?php

namespace App\Models;

use App\Enums\InsuranceRecordStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectInsurance extends Model
{
    protected $fillable = [
        'project_id',
        'provider',
        'policy_number',
        'coverage_start',
        'coverage_end',
        'premium_amount',
        'covered_amount',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'coverage_start' => 'date',
            'coverage_end' => 'date',
            'premium_amount' => 'decimal:2',
            'covered_amount' => 'decimal:2',
            'status' => InsuranceRecordStatus::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
