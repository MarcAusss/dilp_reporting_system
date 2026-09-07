<?php

namespace App\Models;

use App\Enums\ProjectRecordStatus;
use App\Enums\ProjectWorkflowAction;
use App\Enums\ProjectWorkflowStage;
use App\Enums\ProjectWorkflowStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'registry_number',
        'fiscal_year',
        'project_code',
        'title',
        'proponent_id',
        'office_id',
        'fund_source_id',
        'project_type_id',
        'project_purpose_id',
        'implementation_mode_id',
        'date_received',
        'record_status',
        'source_reference',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'date_received' => 'date',
            'record_status' => ProjectRecordStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Project $project): void {
            if (blank($project->registry_number)) {
                $project->forceFill([
                    'registry_number' => sprintf(
                        'DILP-%d-%06d',
                        $project->fiscal_year,
                        $project->getKey()
                    ),
                ])->saveQuietly();
            }

            if (! $project->workflowState()->exists()) {
                $actedAt = $project->created_at ?? now();

                $project->workflowState()->create([
                    'stage' => ProjectWorkflowStage::Evaluation,
                    'status' => ProjectWorkflowStatus::Pending,
                    'assigned_role' => UserRole::DilpCoordinator->value,
                    'started_at' => $actedAt,
                    'last_action_at' => $actedAt,
                    'updated_by' => $project->created_by,
                ]);

                $project->workflowEvents()->create([
                    'from_stage' => null,
                    'from_status' => null,
                    'to_stage' => ProjectWorkflowStage::Evaluation,
                    'to_status' => ProjectWorkflowStatus::Pending,
                    'action' => ProjectWorkflowAction::Registered,
                    'assigned_role' => UserRole::DilpCoordinator->value,
                    'acted_by' => $project->created_by,
                    'acted_at' => $actedAt,
                ]);
            }
        });
    }

    public function proponent(): BelongsTo
    {
        return $this->belongsTo(Proponent::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function fundSource(): BelongsTo
    {
        return $this->belongsTo(FundSource::class);
    }

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class);
    }

    public function projectPurpose(): BelongsTo
    {
        return $this->belongsTo(ProjectPurpose::class);
    }

    public function implementationMode(): BelongsTo
    {
        return $this->belongsTo(ImplementationMode::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(ProjectLocation::class);
    }

    public function primaryLocation(): HasOne
    {
        return $this->hasOne(ProjectLocation::class)
            ->where('is_primary', true);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }
    public function financial(): HasOne
    {
        return $this->hasOne(
            ProjectFinancial::class
        );
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(ProjectBeneficiary::class);
    }

    public function livelihoods(): HasMany
    {
        return $this->hasMany(ProjectLivelihood::class);
    }

    public function budgetItems(): HasMany
    {
        return $this->hasMany(ProjectBudgetItem::class);
    }

    public function convergences(): HasMany
    {
        return $this->hasMany(ProjectConvergence::class);
    }

    public function workflowState(): HasOne
    {
        return $this->hasOne(ProjectWorkflowState::class);
    }

    public function workflowEvents(): HasMany
    {
        return $this->hasMany(ProjectWorkflowEvent::class);
    }

    public function procurements(): HasMany
    {
        return $this->hasMany(ProjectProcurement::class);
    }

    public function obligations(): HasMany
    {
        return $this->hasMany(ProjectObligation::class);
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(ProjectDisbursement::class);
    }

    public function insurances(): HasMany
    {
        return $this->hasMany(ProjectInsurance::class);
    }

    public function implementation(): HasOne
    {
        return $this->hasOne(ProjectImplementation::class);
    }

    public function replacementRequests(): HasMany
    {
        return $this->hasMany(ProjectReplacementRequest::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class);
    }

    public function monitoringVisits(): HasMany
    {
        return $this->hasMany(ProjectMonitoringVisit::class);
    }

    public function monitoringFindings(): HasMany
    {
        return $this->hasMany(ProjectMonitoringFinding::class);
    }

    public function complianceReports(): HasMany
    {
        return $this->hasMany(ProjectComplianceReport::class);
    }


    public function fundingDetail(): HasOne { return $this->hasOne(ProjectFundingDetail::class); }
    public function endorsements(): HasMany { return $this->hasMany(ProjectEndorsement::class); }
    public function complianceCommunications(): HasMany { return $this->hasMany(ProjectComplianceCommunication::class); }
    public function disValidation(): HasOne { return $this->hasOne(ProjectDisValidation::class); }
    public function beneficiaryMetric(): HasOne { return $this->hasOne(ProjectBeneficiaryMetric::class); }
    public function moaRecord(): HasOne { return $this->hasOne(ProjectMoaRecord::class); }
    public function gpaiRecords(): HasMany { return $this->hasMany(ProjectGpaiRecord::class); }
    public function stageMetrics(): HasMany { return $this->hasMany(ProjectStageMetric::class); }
    public function sectorMetrics(): HasMany { return $this->hasMany(ProjectSectorMetric::class); }
    public function livelihoodMetrics(): HasMany { return $this->hasMany(ProjectLivelihoodMetric::class); }
    public function specialProgramMetrics(): HasMany { return $this->hasMany(ProjectSpecialProgramMetric::class); }
    public function reportingInclusions(): HasMany { return $this->hasMany(ProjectReportingInclusion::class); }
    public function postImplementationRecords(): HasMany { return $this->hasMany(ProjectPostImplementationRecord::class); }
    public function convergenceMetrics(): HasMany { return $this->hasMany(ProjectConvergenceMetric::class); }
    public function statusSnapshots(): HasMany { return $this->hasMany(ProjectStatusSnapshot::class); }
}
