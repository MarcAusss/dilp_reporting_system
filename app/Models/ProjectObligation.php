<?php

namespace App\Models;

use App\Enums\ObligationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectObligation extends Model
{
    protected $fillable = [
        'project_id',
        'obligation_number',
        'obligation_date',
        'amount',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'obligation_date' => 'date',
            'amount' => 'decimal:2',
            'status' => ObligationStatus::class,
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
