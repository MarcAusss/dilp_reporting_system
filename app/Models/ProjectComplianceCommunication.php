<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectComplianceCommunication extends Model
{
    protected $fillable = [
        'project_id',
        'type',
        'reference_number',
        'issued_at',
        'received_at',
        'resolved_at',
        'status',
        'remarks',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'received_at' => 'date',
            'resolved_at' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

}
