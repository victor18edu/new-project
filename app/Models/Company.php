<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'corporate_name',
    'trade_name',
    'tax_id',
    'segment_id',
    'lead_source_id',
    'owner_id',
    'website_url',
    'instagram_handle',
    'google_maps_url',
    'website_status',
    'is_mobile_responsive',
    'has_whatsapp_button',
    'has_active_ads',
    'pain_points_notes',
    'address',
    'city',
    'state',
    'postal_code',
    'status',
    'temperature',
])]
class Company extends BaseModel
{
    use SoftDeletes;

    protected $table = 'companies';

    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class, 'segment_id');
    }

    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'company_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'company_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'company_id');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class, 'company_id');
    }
}
