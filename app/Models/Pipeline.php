<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'description',
    'is_default',
    'is_active',
])]
class Pipeline extends BaseModel
{
    protected $table = 'pipelines';

    public function stages(): HasMany
    {
        return $this->hasMany(PipelineStage::class, 'pipeline_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'pipeline_id');
    }
}
