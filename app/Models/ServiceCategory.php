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
class ServiceCategory extends BaseModel
{
    protected $table = 'service_categories';

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'category_id');
    }
}
