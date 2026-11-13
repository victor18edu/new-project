<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'description',
    'icon',
])]
class ActivityType extends BaseModel
{
    protected $table = 'activity_types';

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'activity_type_id');
    }
}
