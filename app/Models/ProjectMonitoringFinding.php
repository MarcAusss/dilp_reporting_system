<?php

namespace App\Models;

use App\Enums\MonitoringFindingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectMonitoringFinding extends Model
{
    protected $fillable = [
        'project_id', 'monitoring_visit_id', 'finding', 'corrective_action', 'due_date',
        'status', 'resolved_at', 'resolution_notes', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'status' => MonitoringFindingStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function visit(): BelongsTo { return $this->belongsTo(ProjectMonitoringVisit::class, 'monitoring_visit_id'); }
    public function followUps(): HasMany { return $this->hasMany(ProjectMonitoringFollowUp::class, 'monitoring_finding_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->lt(today()) && $this->status->isOpen();
    }
}
