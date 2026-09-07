<?php

namespace App\Models;

use App\Enums\BeneficiarySex;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectBeneficiary extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'reference_number',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'sex',
        'birth_date',
        'contact_number',
        'email',
        'address',
        'is_active',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sex' => BeneficiarySex::class,
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(
            BeneficiarySector::class,
            'project_beneficiary_sector'
        )->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function fullName(): string
    {
        return collect([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
        ])->filter(fn (?string $value): bool => filled($value))
            ->implode(' ');
    }
}
