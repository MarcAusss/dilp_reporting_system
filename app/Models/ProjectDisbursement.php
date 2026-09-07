<?php

namespace App\Models;

use App\Enums\DisbursementStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDisbursement extends Model
{
    protected $fillable = [
        'project_id',
        'disbursement_number',
        'payment_reference',
        'payee',
        'prepared_at',
        'forwarded_to_signatories_at',
        'released_at',
        'check_lddap_number',
        'check_lddap_date',
        'dv_number',
        'disbursement_date',
        'amount',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'disbursement_date' => 'date',
            'prepared_at' => 'date',
            'forwarded_to_signatories_at' => 'date',
            'released_at' => 'datetime',
            'check_lddap_date' => 'date',
            'amount' => 'decimal:2',
            'status' => DisbursementStatus::class,
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
