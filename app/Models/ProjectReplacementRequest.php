<?php

namespace App\Models;

use App\Enums\ReplacementRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectReplacementRequest extends Model
{
    protected $fillable = [
        'project_id',
        'reference_number',
        'item_description',
        'reason',
        'requested_amount',
        'request_date',
        'resolution_date',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'requested_amount' => 'decimal:2',
            'request_date' => 'date',
            'resolution_date' => 'date',
            'status' => ReplacementRequestStatus::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
