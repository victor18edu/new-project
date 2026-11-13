<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category_id',
    'name',
    'slug',
    'description',
    'base_price',
    'pricing_type',
    'is_active',
])]
class Service extends BaseModel
{
    protected $table = 'services';

    protected function casts(): array
    {
        return [
            'base_price' => 'string',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProposalItem::class, 'service_id');
    }
}
