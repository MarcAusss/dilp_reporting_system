<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ProjectConvergenceMetric extends Model
{
    protected $fillable = [
        'project_id',
        'convergence_program_id',
        'initiative_name',
        'beneficiary_count',
        'female_count',
        'beneficiary_type',
        'assistance_amount',
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
