<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'description',
    'is_active',
])]
class LostReason extends BaseModel
{
    use SoftDeletes;

    protected $table = 'lost_reasons';

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'lost_reason_id');
    }
}
