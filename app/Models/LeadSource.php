<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'description',
    'channel_type',
    'is_active',
])]
class LeadSource extends BaseModel
{
    protected $table = 'lead_sources';

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'lead_source_id');
    }
}
