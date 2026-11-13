<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'description',
    'is_active',
])]
class Segment extends BaseModel
{
    protected $table = 'segments';

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'segment_id');
    }
}
