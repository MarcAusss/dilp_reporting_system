<?php

namespace App\Models;

use App\Enums\DataImportRowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataImportRow extends Model
{
    protected $fillable = [
        'batch_id', 'source_sheet', 'row_number', 'raw_data', 'normalized_data',
        'status', 'messages', 'duplicate_project_id', 'imported_project_id',
    ];

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'normalized_data' => 'array',
            'messages' => 'array',
            'status' => DataImportRowStatus::class,
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DataImportBatch::class, 'batch_id');
    }

    public function duplicateProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'duplicate_project_id');
    }

    public function importedProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'imported_project_id');
    }
}
