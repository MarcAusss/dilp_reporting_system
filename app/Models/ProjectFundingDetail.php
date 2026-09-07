<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFundingDetail extends Model
{
    protected $fillable = [
        'project_id',
        'adl_nta_number',
        'fund_sponsor',
        'partylist_nga',
        'legislator',
        'lce_congressman_point_person',
        'district',
        'actual_charging',
        'target_barangays',
        'target_municipalities',
        'fund_amount',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'fund_amount' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

}
