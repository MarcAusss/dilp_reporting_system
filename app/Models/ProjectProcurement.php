<?php

namespace App\Models;

use App\Enums\ProcurementStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectProcurement extends Model
{
    protected $fillable = [
        'project_id',
        'reference_number',
        'description',
        'supplier',
        'procurement_date',
        'amount',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'procurement_date' => 'date',
            'amount' => 'decimal:2',
            'status' => ProcurementStatus::class,
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
