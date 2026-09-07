<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDisValidation extends Model
{
    protected $fillable = [
        'project_id',
        'details_tally_with_dis',
        'proposal_status',
        'for_updating',
        'for_coordination_po',
        'for_coordination_co',
        'action_required',
        'resolution',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'details_tally_with_dis' => 'boolean',
            'for_updating' => 'boolean',
            'for_coordination_po' => 'boolean',
            'for_coordination_co' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

}
