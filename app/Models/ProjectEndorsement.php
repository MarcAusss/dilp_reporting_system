<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectEndorsement extends Model
{
    protected $fillable = [
        'project_id',
        'sequence_no',
        'endorsement_date',
        'reference_number',
        'endorsement_type',
        'remarks',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'sequence_no' => 'integer',
            'endorsement_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

}
