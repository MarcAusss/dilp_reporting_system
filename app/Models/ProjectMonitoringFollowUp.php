<?php

namespace App\Models;

use App\Enums\MonitoringFindingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMonitoringFollowUp extends Model
{
    protected $fillable = [
        'monitoring_finding_id', 'follow_up_date', 'notes', 'next_follow_up_date',
        'status_after', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'follow_up_date' => 'date',
            'next_follow_up_date' => 'date',
            'status_after' => MonitoringFindingStatus::class,
        ];
    }

    public function finding(): BelongsTo { return $this->belongsTo(ProjectMonitoringFinding::class, 'monitoring_finding_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
