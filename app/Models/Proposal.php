<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'proposal_number',
    'opportunity_id',
    'company_id',
    'contact_id',
    'owner_id',
    'status',
    'total_one_off',
    'total_recurring',
    'payment_terms',
    'valid_until',
    'sent_at',
    'accepted_at',
    'pdf_path',
    'notes',
])]
class Proposal extends BaseModel
{
    protected $table = 'proposals';

    protected function casts(): array
    {
        return [
            'total_one_off' => 'string',
            'total_recurring' => 'string',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'opportunity_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProposalItem::class, 'proposal_id');
    }
}
