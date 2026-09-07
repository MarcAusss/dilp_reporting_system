<?php

namespace App\Models;

use App\Enums\ProponentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proponent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'abbreviation',
        'head_name',
        'organization_name',
        'organization_classification',
        'contact_person',
        'contact_number',
        'email',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProponentType::class,
            'is_active' => 'boolean',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}