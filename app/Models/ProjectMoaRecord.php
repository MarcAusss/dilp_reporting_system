<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMoaRecord extends Model
{
    protected $fillable = [
        'project_id',
        'received_at',
        'forwarded_for_signature_at',
        'signed_copy_returned_at',
        'notarial_slip_at',
        'forwarded_to_notarial_at',
        'notarized_at',
        'notarized_copy_received_at',
        'original_folder_forwarded_imsd_at',
        'lacking_requirements',
        'commitment',
        'important_note',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'date',
            'forwarded_for_signature_at' => 'date',
            'signed_copy_returned_at' => 'date',
            'notarial_slip_at' => 'date',
            'forwarded_to_notarial_at' => 'date',
            'notarized_at' => 'date',
            'notarized_copy_received_at' => 'date',
            'original_folder_forwarded_imsd_at' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

}
