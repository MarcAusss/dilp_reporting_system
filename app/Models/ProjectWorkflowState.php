<?php

namespace App\Models;

use App\Enums\ProjectWorkflowStage;
use App\Enums\ProjectWorkflowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectWorkflowState extends Model
{
    protected $fillable = [
        'project_id',
        'stage',
        'status',
        'assigned_role',
        'started_at',
        'last_action_at',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'stage' => ProjectWorkflowStage::class,
            'status' => ProjectWorkflowStatus::class,
            'started_at' => 'datetime',
            'last_action_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
