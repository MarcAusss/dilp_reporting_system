<?php

namespace App\Models;

use App\Enums\ProjectRecordStatus;
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
}