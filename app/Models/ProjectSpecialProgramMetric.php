<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ProjectSpecialProgramMetric extends Model
{
    protected $fillable = [
        'project_id',
        'program_name',
        'metric_name',
        'beneficiary_count',
        'female_count',
        'association_count',
        'assistance_amount',
        'beneficiary_or_assistance_detail',
        'status',
        'as_of_date',
        'remarks',
        'created_by',
    ];
    protected function casts(): array { return [
            'beneficiary_count' => 'integer',
            'female_count' => 'integer',
            'association_count' => 'integer',
            'assistance_amount' => 'decimal:2',
            'as_of_date' => 'date',
        ]; }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
}
