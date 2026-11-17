<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
#[Fillable([
    'pipeline_id',
    'name',
    'order_index',
    'probability_percent',
    'slug',
    'is_won_stage',
    'is_lost_stage',
    'color_hex',
])]
class PipelineStage extends BaseModel
{
    use HasFactory;
    protected $table = 'pipeline_stages';

    protected function casts(): array
    {
        return [
            'is_won_stage' => 'boolean',
            'is_lost_stage' => 'boolean',
        ];
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class, 'pipeline_id');
    }
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'stage_id');
    }
    public function fromTransitions(): HasMany
    {
        return $this->hasMany(OpportunityStageHistory::class, 'from_stage_id');
    }
    public function toTransitions(): HasMany
    {
        return $this->hasMany(OpportunityStageHistory::class, 'to_stage_id');
    }
}