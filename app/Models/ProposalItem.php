<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'proposal_id',
    'service_id',
    'item_name',
    'description',
    'quantity',
    'unit_price',
    'discount_amount',
    'total_price',
    'is_recurring',
])]
class ProposalItem extends BaseModel
{
    protected $table = 'proposal_items';

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'string',
            'discount_amount' => 'string',
            'total_price' => 'string',
            'is_recurring' => 'boolean',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class, 'proposal_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
