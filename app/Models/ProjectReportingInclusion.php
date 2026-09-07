<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ProjectReportingInclusion extends Model
{
    protected $fillable = [
        'project_id',
        'report_type',
        'reporting_period',
        'quarter',
        'status',
        'approved_month',
        'reported_at',
        'beneficiary_count',
        'amount',
        'remarks',
        'created_by',
    ];
    protected function casts(): array { return [
            'quarter' => 'integer',
            'approved_month' => 'date',
            'reported_at' => 'date',
            'beneficiary_count' => 'integer',
            'amount' => 'decimal:2',
        ]; }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
}
