<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code',
    'title',
    'company_id',
    'primary_contact_id',
    'pipeline_id',
    'stage_id',
    'owner_id',
    'lost_reason_id',
    'estimated_one_off_value',
    'estimated_recurring_value',
    'currency',
    'expected_close_date',
    'closed_at',
    'status',
    'lost_reason_notes',
    'notes',
])]
class Opportunity extends BaseModel
{
    protected $table = 'opportunities';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function primaryContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'primary_contact_id');
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class, 'pipeline_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'stage_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function lostReason(): BelongsTo
    {
        return $this->belongsTo(LostReason::class, 'lost_reason_id');
    }

    public function stageHistories(): HasMany
    {
        return $this->hasMany(OpportunityStageHistory::class, 'opportunity_id');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class, 'opportunity_id');
    }
}
