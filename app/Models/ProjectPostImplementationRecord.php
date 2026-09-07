<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ProjectPostImplementationRecord extends Model
{
    protected $fillable = [
        'project_id',
        'received_at',
        'inclusions',
        'are_reference',
        'check_awarding_to_proponent_at',
        'awarded_to_beneficiaries_at',
        'forwarded_to_supply_unit_at',
        'status',
        'remarks',
        'created_by',
    ];
    protected function casts(): array { return [
            'received_at' => 'date',
            'check_awarding_to_proponent_at' => 'date',
            'awarded_to_beneficiaries_at' => 'date',
            'forwarded_to_supply_unit_at' => 'date',
        ]; }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
}
