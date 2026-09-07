<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ProjectStatusSnapshot extends Model
{
    protected $fillable = [
        'project_id',
        'dimension',
        'status',
        'as_of_date',
        'beneficiary_count',
        'amount',
        'remarks',
        'created_by',
    ];
    protected function casts(): array { return [
            'as_of_date' => 'date',
            'beneficiary_count' => 'integer',
            'amount' => 'decimal:2',
        ]; }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
}
