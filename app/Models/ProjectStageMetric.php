<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectStageMetric extends Model
{
    protected $fillable = [
        'project_id',
        'stage',
        'beneficiary_count',
        'female_beneficiary_count',
        'amount',
        'as_of_date',
        'remarks',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'beneficiary_count' => 'integer',
            'female_beneficiary_count' => 'integer',
            'amount' => 'decimal:2',
            'as_of_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

}
