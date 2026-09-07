<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundTarget extends Model
{
    protected $fillable = [
        'fiscal_year', 'fund_source_id', 'office_id', 'scope_key', 'allocation_amount',
        'target_projects', 'target_beneficiaries', 'remarks', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'allocation_amount' => 'decimal:2',
            'target_projects' => 'integer',
            'target_beneficiaries' => 'integer',
        ];
    }

    public function fundSource(): BelongsTo { return $this->belongsTo(FundSource::class); }
    public function office(): BelongsTo { return $this->belongsTo(Office::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    public static function scopeKey(?int $officeId): string
    {
        return $officeId ? 'office:'.$officeId : 'regional';
    }
}
