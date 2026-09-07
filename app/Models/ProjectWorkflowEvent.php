<?php

namespace App\Models;

use App\Enums\ProjectWorkflowAction;
use App\Enums\ProjectWorkflowStage;
use App\Enums\ProjectWorkflowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectWorkflowEvent extends Model
{
    protected $fillable = [
        'project_id',
        'from_stage',
        'from_status',
        'to_stage',
        'to_status',
        'action',
        'assigned_role',
        'reference_number',
        'remarks',
        'acted_by',
        'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'from_stage' => ProjectWorkflowStage::class,
            'from_status' => ProjectWorkflowStatus::class,
            'to_stage' => ProjectWorkflowStage::class,
            'to_status' => ProjectWorkflowStatus::class,
            'action' => ProjectWorkflowAction::class,
            'acted_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
