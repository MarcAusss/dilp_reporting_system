<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Barangay extends BaseMasterData
{
    protected $fillable = [
        'municipality_id',
        'code',
        'name',
        'description',
        'is_active',
        'sort_order',
    ];

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }
}