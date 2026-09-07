<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ProjectSectorMetric extends Model
{
    protected $fillable = [
        'project_id',
        'beneficiary_sector_id',
        'sector_name',
        'metric_type',
        'beneficiary_count',
        'female_count',
        'assistance_amount',
        'beneficiary_names',
        'beneficiary_addresses',
        'attribute_detail',
        'reporting_period',
        'remarks',
        'created_by',
    ];
    protected function casts(): array { return [
            'beneficiary_count' => 'integer',
            'female_count' => 'integer',
            'assistance_amount' => 'decimal:2',
        ]; }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
}
