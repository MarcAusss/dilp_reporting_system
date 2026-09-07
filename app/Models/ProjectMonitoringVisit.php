<?php

namespace App\Models;

use App\Enums\MonitoringVisitStatus;
use App\Enums\MonitoringVisitType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectMonitoringVisit extends Model
{
    protected $fillable = [
        'project_id', 'visit_type', 'visit_date', 'status', 'conducted_by_name', 'location',
        'accomplishment_percentage', 'observations', 'recommendations', 'next_monitoring_date',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_type' => MonitoringVisitType::class,
            'visit_date' => 'date',
            'status' => MonitoringVisitStatus::class,
            'accomplishment_percentage' => 'integer',
            'next_monitoring_date' => 'date',
        ];
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function findings(): HasMany { return $this->hasMany(ProjectMonitoringFinding::class, 'monitoring_visit_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
