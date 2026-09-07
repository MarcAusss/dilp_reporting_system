<?php

namespace App\Models;

use App\Enums\ImplementationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectImplementation extends Model
{
    protected $fillable = [
        'project_id',
        'start_date',
        'target_completion_date',
        'actual_completion_date',
        'accomplishment_percentage',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'target_completion_date' => 'date',
            'actual_completion_date' => 'date',
            'accomplishment_percentage' => 'integer',
            'status' => ImplementationStatus::class,
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
