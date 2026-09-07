<?php

namespace App\Models;

use App\Enums\DataImportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataImportBatch extends Model
{
    protected $fillable = [
        'original_filename', 'stored_path', 'file_type', 'status',
        'total_rows', 'valid_rows', 'warning_rows', 'error_rows',
        'duplicate_rows', 'imported_rows', 'skipped_rows', 'headers',
        'header_map', 'options', 'validation_summary', 'failure_message',
        'validated_at', 'started_at', 'completed_at', 'uploaded_by', 'committed_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DataImportStatus::class,
            'headers' => 'array',
            'header_map' => 'array',
            'options' => 'array',
            'validation_summary' => 'array',
            'validated_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'total_rows' => 'integer',
            'valid_rows' => 'integer',
            'warning_rows' => 'integer',
            'error_rows' => 'integer',
            'duplicate_rows' => 'integer',
            'imported_rows' => 'integer',
            'skipped_rows' => 'integer',
        ];
    }

    public function rows(): HasMany
    {
        return $this->hasMany(DataImportRow::class, 'batch_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function committer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'committed_by');
    }

    public function canCommit(): bool
    {
        return $this->status === DataImportStatus::Ready
            && $this->error_rows === 0
            && ($this->valid_rows + $this->warning_rows) > 0;
    }
}
