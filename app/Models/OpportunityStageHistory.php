<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'opportunity_id',
    'from_stage_id',
    'to_stage_id',
    'user_id',
    'duration_in_seconds',
])]
class OpportunityStageHistory extends BaseModel
{
    protected $table = 'opportunity_stage_history';

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'opportunity_id');
    }

    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'from_stage_id');
    }

    public function toStage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'to_stage_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
