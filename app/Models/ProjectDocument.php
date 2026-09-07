<?php

namespace App\Models;

use App\Enums\ProjectDocumentCategory;
use App\Enums\ProjectDocumentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDocument extends Model
{
    protected $fillable = [
        'project_id', 'category', 'name', 'reference_number', 'document_date', 'due_date',
        'submitted_at', 'verified_at', 'status', 'file_disk', 'file_path', 'original_name',
        'mime_type', 'file_size', 'remarks', 'verified_by', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => ProjectDocumentCategory::class,
            'status' => ProjectDocumentStatus::class,
            'document_date' => 'date',
            'due_date' => 'date',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    public function hasFile(): bool { return filled($this->file_path); }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->lt(today())
            && ! in_array($this->status, [ProjectDocumentStatus::Submitted, ProjectDocumentStatus::Verified], true);
    }
}
