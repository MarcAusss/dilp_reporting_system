<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ProjectLivelihoodMetric extends Model
{
    protected $fillable = [
        'project_id',
        'livelihood_id',
        'livelihood_name',
        'beneficiary_count',
        'female_count',
        'assistance_amount',
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
