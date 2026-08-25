<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends BaseMasterData
{
    public function municipalities(): HasMany
    {
        return $this->hasMany(Municipality::class);
    }
}