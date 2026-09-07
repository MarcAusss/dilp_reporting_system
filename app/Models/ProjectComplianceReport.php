<?php

namespace App\Models;

use App\Enums\ComplianceReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectComplianceReport extends Model
{
    protected $fillable = [
        'project_id', 'report_type', 'period_label', 'period_start', 'period_end', 'due_date',
        'submitted_at', 'verified_at', 'status', 'accomplishment_percentage', 'document_id',
        'remarks', 'verified_by', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'due_date' => 'date',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'status' => ComplianceReportStatus::class,
            'accomplishment_percentage' => 'integer',
        ];
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function document(): BelongsTo { return $this->belongsTo(ProjectDocument::class, 'document_id'); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->lt(today())
            && ! in_array($this->status, [ComplianceReportStatus::Submitted, ComplianceReportStatus::Verified, ComplianceReportStatus::Waived], true);
    }
}
